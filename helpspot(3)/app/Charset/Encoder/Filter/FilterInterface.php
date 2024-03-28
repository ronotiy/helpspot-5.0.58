<?php

namespace HS\Charset\Encoder\Filter;

interface FilterInterface
{
    /**
     * Filter a string in some manner.
     *
     * @param  string   The string to be filtered
     * @return string   The filtered string
     */
    public function filter($input);
}
