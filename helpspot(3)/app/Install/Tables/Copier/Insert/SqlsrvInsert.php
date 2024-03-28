<?php

namespace HS\Install\Tables\Copier\Insert;

use HS\Install\Tables\Table;
use Illuminate\Database\Connection;

class SqlsrvInsert implements TableInserter
{
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $destination;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $source;

    /**
     * @var \HS\Install\Tables\Table
     */
    protected $table;

    public function __construct(Connection $destination, Connection $source, Table $table)
    {
        $this->destination = $destination;
        $this->source = $source;
        $this->table = $table;
    }

    /**
     * Insert rows of data into table for direct copying.
     * @param array $rows
     * @return mixed
     */
    public function insert(array $rows)
    {
        // Pagination in SqlServer adds a faux-column
        // which we need to remove
        foreach ($rows as $key => $row) {
            if (array_key_exists('row_num', $row)) {
                unset($rows[$key]['row_num']);
            }
        }

        // Tables with increment columns require us to tell
        // SqlServer to allow insertions of primary key columns
        if ($this->table->incrementColumn) {
            $query = $this->destination->table($this->table->name);

            list($rows, $bindings) = $this->rowsToBinding($rows);

            // Assumption:
            // There are no tables with more than one blob column
            $blobColumn = null;
            $rowsWithConvert = null;
            foreach ($this->table->columns as $column) {
                if ($column->type == 'blob') {
                    $blobColumn = $column->name;
                }
            }

            /**
             * If we have rows inserting NULL into a Varbinary, we need the PDO parameter
             * to appear like "CONVERT(VARBINARY, ?)" instead of simply "?".
             *
             * To do so, we need the rows value to be an Expression object, wo we create a
             * second set of $rows with the Expression object, while keeping the original $rows
             * intact.
             *
             * The original $rows is still used for data insertion.
             *
             * We are essentially fooling Illuminate & PDO into this:
             * PDO::QUERY(
             *    'INSERT INTO tablex (fieldA, fieldB) VALUES (?, CONVERT(VARBINARY(MAX), ?))',
             *    ['whateverA', NULL]
             * );
             */
            $blobColumnKey = -1;
            if (! is_null($blobColumn)) {
                $rowsWithConvert = [];

                foreach ($rows as $key => $row) {
                    $row[$blobColumn] = $this->destination->raw('CONVERT(VARBINARY(MAX), ?)');
                    $rowsWithConvert[$key] = $row;

                    $count = 1;
                    foreach ($row as $column => $value) {
                        if ($column == $blobColumn) {
                            $blobColumnKey = $count;
                        }
                        $count++;
                    }
                }
            }

            $rowsForInsertion = is_null($rowsWithConvert) ? $rows : $rowsWithConvert;
            $insertSQL = $this->destination->getQueryGrammar()->compileInsert($query, $rowsForInsertion);
            $query = 'SET IDENTITY_INSERT '.$this->table->name.' ON; '.$insertSQL.'; SET IDENTITY_INSERT '.$this->table->name.' OFF;';

            if (! is_null($blobColumn)) {
                // Tables with blobs are chunked one row at a time
                $stmt = $this->destination->getPdo()->prepare($query);

                foreach ($bindings as $key => $bind) {
                    if ($key == $blobColumnKey - 1) {
                        // The things we do for binary love
                        $stmt->bindParam($key + 1, $bindings[$key], \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
                    } else {
                        $stmt->bindParam($key + 1, $bindings[$key]);
                    }
                }

                $stmt->execute();
            } else {
                // Bindings are not in the same order as $rows column names when insert sql created
                $this->destination->statement($query, $bindings);
            }
        } else {
            $this->destination->table($this->table->name)->insert($rows);
        }
    }

    /**
     * Convert rows to flat array needed
     * for PDO sql-statement binding.
     * @param array $rows
     * @return array
     */
    protected function rowsToBinding(array $rows)
    {
        // Taken (directly) from \Illuminate/Database/Query/Builder::insert()
        // @link https://github.com/laravel/framework/blob/4.2/src/Illuminate/Database/Query/Builder.php#L1798
        if (! is_array(reset($rows))) {
            $rows = [$rows];
        }

        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient for building these
        // inserts statements by verifying the elements are actually an array.
        else {
            foreach ($rows as $key => $value) {
                ksort($value);
                $rows[$key] = $value;
            }
        }

        // We'll treat every insert like a batch insert so we can easily insert each
        // of the records into the database consistently. This will make it much
        // easier on the grammars to just handle one type of record insertion.
        $bindings = [];

        foreach ($rows as $record) {
            foreach ($record as $column => $value) {
                if ($column == 'blobFile' && ! is_null($value)) {
                    $value = $this->getBinary('blobFile', 'xDocumentId', $record['xDocumentId']);
                }

                if ($column == 'blobPhoto' && ! is_null($value)) {
                    $value = $this->getBinary('blobPhoto', 'xPersonPhotoId', $record['xPersonPhotoId']);
                }

                $bindings[] = $value;
            }
        }

        return [$rows, $bindings];
    }

    protected function getBinary($column, $identifier, $id)
    {
        $table = $this->table->name;

        $pdo = $this->source->getPdo();
        $query = "SELECT $column FROM $table WHERE $identifier = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        $stmt->bindColumn(1, $binaryFile, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        $stmt->fetch(\PDO::FETCH_BOUND);

        return $binaryFile;
    }
}
