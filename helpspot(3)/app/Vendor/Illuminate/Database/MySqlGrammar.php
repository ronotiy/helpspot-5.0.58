<?php

namespace HS\Vendor\Illuminate\Database;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as BaseGrammar;

class MySqlGrammar extends BaseGrammar
{
    /**
     * Compile an index creation command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  string  $type
     * @return string
     */
    protected function compileKey(Blueprint $blueprint, Fluent $command, $type)
    {
        $columns = $this->columnize($command->columns, $command->length);
        $table = $this->wrapTable($blueprint);

        return "alter table {$table} add {$type} {$command->index}($columns)";
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns, $length = null)
    {
        $wrappedColumns = array_map([$this, 'wrap'], $columns);

        if ($length && $length > 0) {
            foreach ($wrappedColumns as $key => $wrappedColumn) {
                $wrappedColumns[$key] = $wrappedColumn.'('.$length.')';
            }
        }

        return implode(', ', $wrappedColumns);
    }

    /**
     * Create the column definition for a large binary type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeLongBinary(Fluent $column)
    {
        return 'longblob';
    }
}
