<?php

namespace HS\Install\Tables\Copier\Insert;

interface TableInserter
{
    /**
     * Insert rows of data into table for direct copying.
     * @param array $rows
     * @return mixed
     */
    public function insert(array $rows);
}
