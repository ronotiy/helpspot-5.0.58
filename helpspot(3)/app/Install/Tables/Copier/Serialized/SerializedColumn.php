<?php

namespace HS\Install\Tables\Copier\Serialized;

interface SerializedColumn
{
    /**
     * Encode serialized string as required per
     * field, as each serialized data type is unique.
     * @param  string $string
     * @return string
     */
    public function encode($string);
}
