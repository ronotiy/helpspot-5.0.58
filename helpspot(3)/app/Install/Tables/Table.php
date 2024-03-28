<?php

namespace HS\Install\Tables;

class Table
{
    use \HS\Base\Gettable;

    protected $name;

    protected $incrementColumn;

    protected $needsEncoding;

    protected $legacyColumns = [];

    protected $truncate;

    protected $columns;

    protected $encodeColumns = [];

    public function __construct(stdClass $tableData)
    {
        $this->name = $tableData->name;
        $this->incrementColumn = $tableData->incrementColumn;
        $this->needsEncoding = ($tableData->passThru === false);
        $this->legacyColumns = $tableData->legacyColumns;
        $this->truncate = $tableData->truncate;
        $this->columns = $tableData->columns;

        $this->setEncodeColumns($this->columns);
    }

    protected function setEncodeColumns($columns)
    {
        foreach ($columns as $column) {
            if ($column->encode) {
                $this->encodeColumns[] = $column;
            }
        }
    }
}
