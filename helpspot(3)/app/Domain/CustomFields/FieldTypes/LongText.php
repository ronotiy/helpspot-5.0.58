<?php

namespace HS\Domain\CustomFields\FieldTypes;

use Illuminate\Database\Schema\Blueprint;

trait LongText
{
    public function addColumn(Blueprint $table)
    {
        $fieldName = 'Custom'.$this->xCustomField;

        $table->longText($fieldName)->nullable();
    }

    public function dropColumn(Blueprint $table)
    {
        $fieldName = 'Custom'.$this->xCustomField;
        $table->dropColumn($fieldName);
    }

    public function searchable()
    {
        return true;
    }
}
