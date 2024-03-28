<?php

namespace HS\Domain\CustomFields\FieldTypes;

use Illuminate\Database\Schema\Blueprint;

trait Decimal
{
    public function addColumn(Blueprint $table)
    {
        $fieldName = 'Custom'.$this->xCustomField;

        $table->decimal($fieldName, 10, $this->iDecimalPlaces)->nullable();
        $table->index([$fieldName]);
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
