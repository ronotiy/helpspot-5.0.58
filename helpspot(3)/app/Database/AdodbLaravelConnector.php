<?php

namespace HS\Database;

use DB;
use HS\Attachments\Manager;
use HS\Attachments\Documents;

class AdodbLaravelConnector
{
    // Not sure if we'll need this, used to be a bug with ADODB where we had to set this
    // on the global DB object for MSSQL. Leaving it here for now until MSSQL is further implemented.
    public $identitySQL = 'select @@IDENTITY';

    /**
     * @param $sql
     * @param array $bindings
     * @return RecordSet|bool
     */
    public function Execute($sql, $bindings = [])
    {
        $result = $this->_execute($sql, $bindings);

        if (is_array($result)) {
            return new RecordSet($result);
        }

        return $result;
    }

    /**
     * @param $sql
     * @param array $bindings
     * @return array|bool
     */
    public function GetRow($sql, $bindings = [])
    {
        $result = $this->Execute($sql, $bindings);

        $firstRow = $result->FetchRow();

        unset($result);

        return $firstRow;
    }

    /**
     * @param $sql
     * @param array $bindings
     * @return mixed
     */
    public function GetOne($sql, $bindings = [])
    {
        $result = $this->Execute($sql, $bindings);

        $firstRow = $result->FetchRow();

        unset($result);

        if($firstRow === false) {
            return null;
        }
        return (! is_array($firstRow))
            ? null
            : $firstRow[array_keys($firstRow)[0]];// GetOne must return a single column value
    }

    /**
     * @param $sql
     * @param array $bindings
     * @return array
     */
    public function GetCol($sql, $bindings = [])
    {
        $result = $this->Execute($sql, $bindings);

        $column = [];

        while ($row = $result->FetchRow()) {
            $column[] = reset($row);
        }

        unset($result);

        return $column;
    }

    /**
     * @param $sql
     * @param array $bindings
     * @return array
     */
    public function GetArray($sql, $bindings = [])
    {
        return array_map(function ($item) {
            return (array) $item;
        }, $this->_execute($sql, $bindings));
    }

    /**
     * @param $sql
     * @param bool $rows
     * @param bool $offset
     * @param array $bindings
     * @return array|RecordSet
     */
    public function SelectLimit($sql, $rows = false, $offset = false, $bindings = [])
    {

        // We need to do this different for each database as MSSQL doesn't really support offsets
        // so in that case we grab everything up through the offset to reduce load as much as possible
        // and then slice out just the rows we want in PHP code. This is also how ADODB did it.
        // Laravel does do a crazy tmp table thing to do it in DB, but sticking with this simpler way for now.

        if (config('database.default') == 'mysql') {
            if ($offset) {
                $sql = $sql." limit {$offset},{$rows}";
            } else {
                $sql = $sql." limit {$rows}";
            }

            $result = $this->Execute($sql, $bindings);

            return $result;
        }

        if (config('database.default') == 'sqlsrv') {
            if ($offset) {
                $total = (int) $rows + (int) $offset;
                $sql = preg_replace('/(^\s*select\s+(distinctrow|distinct)?)/i', '\\1 TOP '.$total.' ', $sql);
            } else {
                $sql = preg_replace('/(^\s*select\s+(distinctrow|distinct)?)/i', '\\1 TOP '.((int) $rows).' ', $sql);
            }

            $result = $this->Execute($sql, $bindings);

            return $result->Slice($rows, $offset);
        }
    }

    /**
     * Underlying execute method, moved to protected method
     * so we can call it in a way that gives us an array result.
     * @param $sql
     * @param $bindings
     * @return mixed
     */
    protected function _execute($sql, $bindings)
    {
        $type = strtoupper(explode(' ', utf8_trim($sql))[0]);

        if (in_array($type, ['SELECT', 'INSERT', 'UPDATE', 'DELETE'])) {
            $result = DB::$type($sql, $bindings);
        } elseif ($type == 'SHOW') {
            $result = DB::select($sql);
        } else {
            // SET, ALTER
            $result = DB::statement($sql, $bindings);
        }

        return ($result === 0) ? true : $result;
    }

    public function UpdateBlob($table, $column, $val, $where, $blobtype = 'BLOB')
    {
        /** @var Documents $attachments */
        $attachments = app(Manager::class)->connection();

        // Parse $where to be $identifier = $id
        // HelpSpot only happens to use a simple "where", usually `xDocumentId = $id`
        $whereParts = explode('=', $where);
        $identifier = trim($whereParts[0]);
        $id = trim($whereParts[1]);

        return $attachments->putBinary($table, $column, $identifier, $id, $val);
    }

    public function Insert_ID()
    {
        return DB::getPdo()->lastInsertId();
    }

    public function StartTrans()
    {
        return DB::beginTransaction();
    }

    public function CompleteTrans()
    {
        return DB::commit();
    }

    public function FailTrans()
    {
        return DB::rollBack();
    }

    // Is there a way to detect this?
    // I think Laravel will raise exceptions.
    public function HasFailedTrans()
    {
        return false;
    }

    // In ADODB this made a recordset properly serializable, but we should be able to just pass this through
    public function _rs2rs($recordset)
    {
        return $recordset;
    }
}
