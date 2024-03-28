<?php

namespace HS\Domain\CustomFields\FieldTypes;

use Illuminate\Database\Schema\Blueprint;

trait TinyInteger
{
    public function addColumn(Blueprint $table)
    {
        $fieldName = 'Custom'.$this->xCustomField;

        $table->tinyInteger($fieldName)->nullable();
    }

    public function dropColumn(Blueprint $table)
    {
        $fieldName = 'Custom'.$this->xCustomField;
        $table->dropColumn($fieldName);
    }

    public function searchable()
    {
        return false;
    }
}
