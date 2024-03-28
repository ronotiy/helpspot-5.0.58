<?php

namespace HS\Charset\Encoder\Filter;

class CharacterFilter implements FilterInterface
{
    /**
     * Copying and Pasting from Word (and other common situations)
     * result in special characters which cause encoding issues.
     * We clean special character quotes, dashes, elipses
     * Fixes #406.
     *
     * @param  string   The string to be cleaned of special characters
     * @return string   The string with special characters replaced
     */
    public function filter($input)
    {
        // Replace UTF-8 Special Characters
        $replaced = str_replace(
                    ["\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"],
                    ["'", "'", '"', '"', '-', '--', '...'],
                    $input);

        // Replace Windows-1252 Special Characters
        $replaced = str_replace(
                    [chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)],
                    ["'", "'", '"', '"', '-', '--', '...'],
                    $replaced);

        return $replaced;
    }
}
