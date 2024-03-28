<?php

namespace HS\Database;

use HS\Install\Tables\Table;

class LegacyConnection
{
    /**
     * @var \ADOConnection
     */
    private $adodb;

    public function __construct(\ADOConnection $adodb)
    {
        $this->adodb = $adodb;
    }

    public function getColumnValue(Table $table, $columnName, $row)
    {
        if ($table->name == 'HS_Settings') {
            $rowResult = $this->adodb->GetRow('SELECT tValue FROM  HS_Settings WHERE sSetting = ?', [$row->sSetting]);

            return $rowResult['tvalue'];
        }

        $incrementColumn = $table->incrementColumn;
        $rowResult = $this->adodb->GetRow('SELECT '.$columnName.' FROM '.$table->name.' WHERE '.$incrementColumn.' = ?', [$row->$incrementColumn]);

        return $rowResult[$columnName];
    }
}
