<?php

namespace HS\Install\Tables\Copier;

use HS\Install\Tables\Table;

class BasicCopier extends AbstractCopier
{
    /**
     * A naive approach, inserts all rows
     * given in one bulk query
     * Pro: Simple
     * Con: Loads all rows into memory, inserts all in one query
     * Recommendation: Use for small size tables
     * TODO: Transaction Support?
     * @param Table $table
     * @return mixed|void
     */
    public function copy(Table $table)
    {
        $rows = $this->srcConnection->table($table->name)->get();

        if ($table->needsEncoding) {
            foreach ($rows as $key => $row) {
                $rows[$key] = $this->encodeRow($table->columns, $row);
            }
        }

        $this->destConnection->table($table->name)->insert($rows);
    }
}
