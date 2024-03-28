<?php

namespace HS\Domain\CustomFields;

use Illuminate\Database\Schema\Blueprint;

interface Field
{
    public function addColumn(Blueprint $table);

    public function dropColumn(Blueprint $table);

    /**
     * Determine if field should
     * be indexed for search.
     * @return bool
     */
    public function searchable();
}
