<?php

namespace HS\Domain\CustomFields\FieldTypes;

use Illuminate\Database\Schema\Blueprint;

trait StringType
{
    public function addColumn(Blueprint $table)
    {
        $fieldName = 'Custom'.$this->xCustomField;
        $table->string($fieldName, $this->getSize())->nullable();
        $table->index([$fieldName], null, $this->indexSize());
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

    protected function getSize()
    {
        // Use 255 by default.
        return ($this->sTxtSize) ? $this->sTxtSize : 255;
    }

    protected function indexSize()
    {
        return ($this->getSize() < 20)
            ? $this->getSize()
            : 20;
    }
}
