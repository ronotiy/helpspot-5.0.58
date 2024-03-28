<?php

namespace HS\Vendor\Illuminate\Database;

use Illuminate\Database\Schema\Blueprint as Baseprint;

class Blueprint extends Baseprint
{
    /**
     * Specify an index for the table.
     *
     * @param  string|array $columns
     * @param  string $name
     * @param  int $length
     * @return \Illuminate\Support\Fluent
     */
    public function index($columns, $name = null, $length = null)
    {
        return $this->indexCommand('index', $columns, $name, $length);
    }

    /**
     * Add a new index command to the blueprint.
     *
     * @param  string        $type
     * @param  string|array  $columns
     * @param  string        $index
     * @return \Illuminate\Support\Fluent
     */
    protected function indexCommand($type, $columns, $index, $length = null)
    {
        $columns = (array) $columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        if (is_null($index)) {
            $index = $this->createIndexName($type, $columns);
        }

        return $this->addCommand($type, compact('index', 'columns', 'length'));
    }

    /**
     * Create a new large binary column on the table.
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function longBinary($column)
    {
        return $this->addColumn('longBinary', $column);
    }
}
