<?php

namespace HS\Install\Tables\Copier\Insert;

use HS\Install\Tables\Table;
use Illuminate\Database\Connection;

class PgsqlInsert implements TableInserter
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
        foreach ($rows as $key => $row) {
            // pgsql may have this as a lowercase column
            // in the source database
            if (array_key_exists('scid', $row)) {
                $row['sCID'] = $row['scid'];
                unset($row['scid']);
            }

            $rows[$key] = $row;
        }

        $this->destination->table($this->table->name)->insert($rows);
    }
}
