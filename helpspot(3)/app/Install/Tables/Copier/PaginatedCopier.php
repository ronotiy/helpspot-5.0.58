<?php

namespace HS\Install\Tables\Copier;

class PaginatedCopier extends AbstractCopier
{
    protected $processSingleRows = [
        'HS_Documents',
        'HS_KB_Documents',
        'HS_Person_Photos',
    ];

    /**
     * Grab and process rows from database using LIMIT/OFFSET (pagination)
     * Pro: Lowest amount of data loaded into memory at a time
     *      Lowest overall memory usage!
     *      The quickest on a large-ish data set
     * Con: More SQL read/write queries
     * Recommendation: Use for large size tables
     * TODO: Transaction Support?
     * @return mixed|void
     */
    public function run()
    {
        $chunk = 50;

        /*
         * Documents table rows can be large
         * so we should do one at a time to avoid issues with
         * memory & mysql max_allowed_packet
         */
        if (in_array($this->table->name, $this->processSingleRows)) {
            $chunk = 1;
        }

        /*
         * Sometimes the HS_Request table has `Custom#` columns despite
         * the custom field being erased in the past. Here we screen
         * out `Custom#` columns that don't have a corresponding record
         * in the `HS_CustomFields` tables
         */
        $customFieldColumns = [];
        if ($this->table->name == 'HS_Request') {
            // See AbstractCopier::copy for setting static::customFieldColumns
            $customFieldColumns = $this->customFieldColumns;
        }

        $this->query->chunk($chunk, function ($rows) use ($customFieldColumns) {
            foreach ($rows as $key => $row) {
                /*
                 * If column is "legacy", it shouldn't be copied
                 * and we can safely unset it here
                 */
                foreach ($this->table->legacyColumns as $colName) {
                    if (property_exists($row, $colName)) {
                        unset($row->$colName);
                    }
                }

                /*
                 * Check all HS_Request columns
                 * If Column starts with "Custom" And it is
                 * not in the array $customFieldColumns,
                 * Unset it
                 */
                if ($this->table->name == 'HS_Request') {
                    // stdClass is iterable
                    foreach ($row as $column => $value) {
                        if (substr($column, 0, 6) === 'Custom' && ! in_array($column, $customFieldColumns)) {
                            unset($row->$column);
                        }
                    }
                }

                /*
                 * Don't copy HS_Filters.fPermissionGroup,
                 * it's going away in version 4.0.0+
                 */
                if ($this->table->name == 'HS_Filters' || $this->table->name == 'HS_Responses') {
                    unset($row->fPermissionGroup);
                }

                /*
                 * Don't copy HS_Person.tMobileSignature
                 * nor HS_Person.fIphoneDefaultMyqueue,
                 * they are going away in version 4.0.0+
                 */
                if ($this->table->name == 'HS_Person') {
                    unset($row->tMobileSignature);
                    unset($row->fIphoneDefaultMyqueue);
                }

                if ($this->table->needsEncoding) {
                    $row = $this->encodeRow($this->table->columns, $row);
                }

                /*
                 * Clean/Purify HTML from old requests to
                 * this request. Must be performed on UTF-8
                 * content, this happens post-encoding.
                 */
                if ($this->table->name == 'HS_Request_History') {
                    if ($row->fNoteIsHTML == 1) {
                        $row->tNote = $this->clean($row->tNote);
                    }
                }

                $rows[$key] = (array) $row;
            }

            $inserter = $this->getInserter($this->destConnection->getDriverName());

            // For Bayesian Tables, there may be duplicates caught despite pasted "uniqueness" of entries
            // so we'll just gobble any such error and move on, since we can afford to lose some spam training data
            if ($this->table->name === 'HS_Bayesian_Corpus' || $this->table->name === 'HS_Portal_Bayesian_Corpus') {
                try {
                    $inserter->insert($rows);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Continue onward, these rows just won't get inserted
                }
            } else {
                $inserter->insert($rows);
            }
        });

        // If the table has a increment column (primary key),
        // reset the auto-increment value if needed
        if ($this->table->incrementColumn) {
            $this->updateAutoIncrementValue($this->table->name, $this->table->incrementColumn);
        }
    }

    /**
     * @param $dbDriverName
     * @return \HS\Install\Tables\Copier\Insert\TableInserter
     */
    protected function getInserter($dbDriverName)
    {
        $class = 'HS\\Install\\Tables\\Copier\\Insert\\'.ucfirst($dbDriverName).'Insert';

        return new $class($this->destConnection, $this->srcConnection, $this->table);
    }
}
