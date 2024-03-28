<?php

namespace HS\Install\Tables\Copier;

use HS\Install\Tables\Table;

class ChunkedCopier extends AbstractCopier
{
    /**
     * Loads all rows into memory but does
     * batch encoding and insertion of rows in chunks
     * Pro: Reduces number of (slow) update queries
     * Con: Loading all rows into memory
     * Recommendation: Use for medium size tables
     * TODO: Transaction support?
     * @param Table $table
     * @return mixed|void
     */
    public function copy(Table $table)
    {
        $rows = $this->srcConnection->table($table->name)->get();

        $totalRows = count($rows);

        // Copy all rows to destination
        $rowQueue = [];
        $count = 0;

        foreach ($rows as $row) {
            if ($table->needsEncoding) {
                $row = $this->encodeRow($table->columns, $row);
            }

            $rowQueue[] = (array) $row;
            $count++;

            // Flush every 50 rows or when we reach the last row
            if (($count % 50 === 0) || ($count == $totalRows)) {
                $this->destConnection->table($table->name)->insert($rowQueue);
                $rowQueue = [];
            }
        }

        unset($rows); // Clean out $rows if occurs in iteration
    }
}
