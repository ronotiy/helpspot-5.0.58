<?php

namespace HS\Install\Tables\Copier\Serialized;

use HS\Charset\Encoder\Manager;

abstract class AbstractSerializedEncoder implements SerializedColumn
{
    /**
     * @var Manager
     */
    private $encoder;

    /**
     * @var string
     */
    private $encodeFrom;

    /**
     * @var string
     */
    private $encodeTo;

    /**
     * @var bool
     */
    protected $isSqlServer;

    public function __construct(Manager $encoder, $encodeFrom = 'ISO-8859-1', $encodeTo = 'UTF-8', $isSqlServer = false)
    {
        $this->encoder = $encoder;
        $this->encodeFrom = $encodeFrom;
        $this->encodeTo = $encodeTo;
        $this->isSqlServer = $isSqlServer;
    }

    /**
     * An abstract encoder, which covers a few use cases in HelpSpot
     * and so deserves a spot in the abstract class, to keep
     * things a little DRYer. It's like an umbrella, just not those
     * awesome huge ones.
     *
     * Encode serialized string as required per
     * field, as each serialized data type is unique
     * @param  string $string
     * @return string|bool
     */
    public function encode($string)
    {
        /*
         * Assumes all serialized objects in HS_Settings are
         * a serialized array, which starts with "a:"
         */
        if (empty($string) || strpos($string, 'a:') !== 0) {
            return $string;
        }

        $array = $this->mb_unserialize($string);

        if (! $array) {
            $array = unserialize($string);
        }

        if (is_array($array) && ! $this->isSqlServer) {
            $array = $this->recursiveEncode($array);
        }

        if (is_array($array)) {
            return serialize($array);
        }

        return false;
    }

    protected function encodeString($string)
    {
        if ($this->isSqlServer) {
            return $string;
        }

        return $this->encoder->encode($string, $this->encodeTo, $this->encodeFrom);
    }

    /**
     * Walk through all non-array items (recursively) and encode them.
     * @param array $array
     * @return array
     */
    protected function recursiveEncode(array $array)
    {
        if ($this->isSqlServer) {
            return $array;
        }

        array_walk_recursive($array, function (&$item, $key) {
            if (! is_numeric($item) && is_string($item)) {
                $item = $this->encodeString($item);
            }
        });

        return $array;
    }

    /**
     * Recalculate byte length of strings within serialized php data
     * This is required as new database drivers may implicitly
     * convert data to unicode/utf8/something and so miscalculate the
     * bytes actually inserted in version 3 of HelpSpot.
     *
     * @link http://stackoverflow.com/a/27924449/1412984
     * @param $string
     * @return mixed
     */
    protected function mb_unserialize($string)
    {
        $string2 = preg_replace_callback(
            '!s:(\d+):"(.*?)";!s',
            function ($m) {
                $len = strlen($m[2]);
                $result = "s:$len:\"{$m[2]}\";";

                return $result;
            },
            $string);

        return unserialize($string2);
    }
}
