<?php

namespace HS\Install\Tables\Copier\Insert;

use HS\Install\Tables\Table;
use Illuminate\Database\Connection;

class MysqlInsert implements TableInserter
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
        // In case we convert SqlServer to MySQL
        // in a last-ditch effort get around the utter
        // terribleness of MySQL Workbench migrations
        foreach ($rows as $key => $row) {
            if (array_key_exists('row_num', $row)) {
                unset($rows[$key]['row_num']);
            }
        }

        $this->destination->table($this->table->name)->insert($rows);
    }
}
