<?php

namespace HS\Vendor\Illuminate\Database;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as BaseGrammar;

class SqlServerGrammar extends BaseGrammar
{
    /**
     * Create the column definition for a large binary type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeLongBinary(Fluent $column)
    {
        return 'varbinary(max)';
    }
}
