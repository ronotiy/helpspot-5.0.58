<?php

namespace HS\Install\Tables\Copier;

use HS\Install\Tables\Table;
use HS\Charset\Encoder\Manager;
use HS\Html\Clean\CleanerInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\SqlServerConnection;

abstract class AbstractCopier implements TableCopier
{
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $srcConnection;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $destConnection;

    /**
     * @var CleanerInterface
     */
    protected $cleaner;

    /**
     * @var \HS\Charset\Encoder\Manager
     */
    protected $encoder;

    /**
     * @var string
     */
    protected $encodeFrom;

    /**
     * @var string
     */
    protected $encodeTo;

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * @var \HS\Install\Tables\Table
     */
    protected $table;

    /**
     * Valid Custom field ID's.
     * @var array
     */
    protected $customFieldColumns;

    /**
     * Are we copying to/from SqlServer.
     * @var bool
     */
    protected $isSqlServer = false;

    /**
     * The methods that should trigger the return of the query builder.
     * @var array
     */
    protected $passthru = [
        'toSql', 'lists', 'insert', 'insertGetId', 'pluck', 'count',
        'min', 'max', 'avg', 'sum', 'exists', 'getBindings',
    ];

    public function __construct(
        Connection $srcConnection, Connection $destConnection, CleanerInterface $cleaner, Manager $encoder, $encodeFrom = 'ISO-8859-1', $encodeTo = 'UTF-8')
    {
        $this->srcConnection = $srcConnection;
        $this->destConnection = $destConnection;
        $this->cleaner = $cleaner;
        $this->encoder = $encoder;
        $this->encodeFrom = $encodeFrom;
        $this->encodeTo = $encodeTo;

        // Query logging will eat up memory
        $this->srcConnection->disableQueryLog();
        $this->destConnection->disableQueryLog();

        $this->isSqlServer = ($this->destConnection instanceof SqlServerConnection);
    }

    /**
     * Set table used for table copying
     * Returns "this", allowing user to set query parameters.
     * @param Table $table
     * @return $this|mixed
     */
    public function copy(Table $table)
    {
        $this->table = $table;

        if ($this->table->name == 'HS_Request') {
            // Get CustomField rows
            $customFields = $this->srcConnection->table('HS_CustomFields')->select('xCustomField')->get();

            $this->customFieldColumns = array_map(function ($customField) {
                return 'Custom'.$customField->xCustomField;
            }, $customFields);
        }

        $this->query = $this->srcConnection->table($table->name);

        if (is_string($table->truncate)) {
            // Only get last 2 weeks of items
            $this->query->where($table->truncate, '>', strtotime('-2 weeks'));
        }

        return $this;
    }

    abstract public function run();

    /**
     * Clean HTML strings.
     * @param string $string
     * @return string
     */
    protected function clean($string)
    {
        return $this->cleaner->clean($string);
    }

    /**
     * Encode a string using encodeFrom/encodeTo
     * variables set in repository.
     * @param $string
     * @throws \Exception
     * @return string
     */
    protected function encode($string)
    {
        if (is_null($this->encoder)) {
            throw new \Exception('Character set encoder not set');
        }

        // Don't encode to a charset string is already set to
        if ($this->normalizeCharset($this->encodeTo) == $this->normalizeCharset($this->encodeFrom)) {
            return $string;
        }

        // Don't encode SqlServer because UNICODE
        // Updated php drivers essentially do conversion for us in SqlServer
        // since they operate in unicode. Or something. That's the theory, anyway.
        if ($this->isSqlServer) {
            return $string;
        }

        return $this->encoder->encode($string, $this->encodeTo, $this->encodeFrom);
    }

    /**
     * Normalize charset string to lowercase and no dashes.
     * @param $charset
     * @return mixed
     */
    protected function normalizeCharset($charset)
    {
        return str_replace('-', '', strtolower($charset));
    }

    /**
     * Encode serialized row.
     * @param $table
     * @param $column
     * @param $string
     * @return mixed
     */
    protected function encodeSerialized($table, $column, $string, $isSqlServer = false)
    {
        $serializedEncoder = $this->getSerializedEncoder($table, $column, $isSqlServer);

        return $serializedEncoder->encode($string);
    }

    /**
     * Encode a database row using determined
     * encode from/to settings.
     * @param array $columns
     * @param \stdClass $row
     * @return \stdClass $row
     */
    protected function encodeRow($columns, $row)
    {
        foreach ($columns as $column) {
            if ($column->encode === false) {
                continue;
            }

            $columnName = $column->name;

            if ($column->serialized === true) {
                $encodedSerialized = $this->encodeSerialized($this->table->name, $columnName, trim($row->$columnName), $this->isSqlServer);

                // If we have issues, we blame encoding and the db connection charset
                // Let's try the legacy DB connection to get the string to encode
                if ($encodedSerialized === false) {
                    /**
                     * Converting from utf8 to source database character set, in case of
                     * odd characters causing de-serialization issues.
                     */
                    //$serializedString = iconv('UTF-8', $this->encodeFrom.'//IGNORE', $row->$columnName);
                    // We need to query the table, the column and an ID
                    $legacyConnection = app()->make(\HS\Database\LegacyConnection::class);
                    $serializedString = $legacyConnection->getColumnValue($this->table, $columnName, $row);

                    $encodedSerialized = $this->encodeSerialized($this->table->name, $columnName, trim($serializedString), $this->isSqlServer);
                }

                $row->$columnName = $encodedSerialized;
            } else {
                $row->$columnName = $this->encode($row->$columnName);
            }

            // Some encoded rows may become longer in length
            // We need to ensure a data type max-length is not breached
            $columnMaxLength = $this->getMaxLength($column);
            if ($columnMaxLength > 0 &&
                strlen($row->$columnName) > $columnMaxLength) {
                $row->$columnName = substr($row->$columnName, 0, $columnMaxLength);
            }
        }

        return $row;
    }

    /**
     * Return max length of a varchar field.
     * @param $column
     * @return int
     */
    protected function getMaxLength($column)
    {
        $length = 0;

        // Match $row->type "varchar(255)" and return integer value
        preg_match('/varchar\((.*?)\)/', $column->type, $matches);

        if (count($matches) == 2 && is_numeric($matches[1])) {
            $length = floor($matches[1]);
        }

        return $length;
    }

    /**
     * Set table auto-increment value based on current maximum
     * Likely only for PostgreSQL.
     * @throws \InvalidArgumentException
     */
    protected function updateAutoIncrementValue($table, $incrementColumn)
    {
        switch ($this->destConnection->getName()) {
            case 'mysql':
                //$this->db->statement( 'ALTER TABLE HS_Request AUTO_INCREMENT=12400' );
                break;
            case 'sqlsrv':
                //$this->db->statement( 'DBCC CheckIdent (HS_Request,RESEED,12400)' );
                break;
            default:
                throw new \InvalidArgumentException('Illegal database connection type given');
        }
    }

    /**
     * Get class used for encoding serialized column.
     * @param $table
     * @param $column
     * @return mixed
     */
    protected function getSerializedEncoder($table, $column, $isSqlServer)
    {
        $table = ucfirst(str_replace('_', '', $table));
        $column = ucfirst(str_replace('_', '', $column));
        $class = 'HS\\Install\\Tables\\Copier\\Serialized\\'.$table.$column;

        return new $class($this->encoder, $this->encodeFrom, $this->encodeTo, $isSqlServer);
    }

    /**
     * Call methods on the query builder, thus allowing us to set conditions
     * on the query used to filter what rows from a table to copy.
     * @param $method
     * @param $parameters
     * @return AbstractCopier|mixed
     */
    public function __call($method, $parameters)
    {
        $result = call_user_func_array([$this->query, $method], $parameters);

        // We ran the method on $this->query, but we're returning $this
        //  unless one of the pass-thru methods was called
        return in_array($method, $this->passthru) ? $result : $this;
    }
}
