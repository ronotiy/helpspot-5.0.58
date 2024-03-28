<?php

namespace HS\Domain\CustomFields\FieldTypes;

use Illuminate\Database\Schema\Blueprint;

trait NullField
{
    public function addColumn(Blueprint $table)
    {
        throw new \Exception('Base CustomField cannot add column');
    }

    public function dropColumn(Blueprint $table)
    {
        throw new \Exception('Base CustomField cannot drop column');
    }

    public function searchable()
    {
        throw new \Exception('Base CustomField cannot determine if searchable');
    }
}
