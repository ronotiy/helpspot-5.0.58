<?php

namespace HS\Install\Tables\Copier;

use HS\Install\Tables\Table;

interface TableCopier
{
    /**
     * Set table used for table copying
     * Returns "this", allowing user to set query parameters.
     * @param Table $table
     * @return $this
     */
    public function copy(Table $table);

    /**
     * Run the copying using the query as defined.
     * @return mixed
     */
    public function run();
}
