<?php

namespace HS\System;

class Features
{
    /**
     * Test if PHP has iconv function.
     *
     * @return bool
     */
    public function hasIconv()
    {
        return  extension_loaded('iconv') || function_exists('iconv');
    }

    /**
     * Test if PHP has mb_convert_encoding function.
     *
     * @return bool
     */
    public function hasMb()
    {
        return  extension_loaded('mbstring') || function_exists('mb_convert_encoding');
    }

    /**
     * Check if Windows encoding is being
     * used which mbstring does not support.
     *
     * Mb String only supports the following
     * windows charsets:
     * - Windows-1251
     * - Windows-1252
     *
     * @link(_blank, http://it2.php.net/manual/en/mbstring.supported-encodings.php)
     *
     * @param  string  $charset Charset used
     * @return bool          If using unsupported Windows charset
     */
    public function mbstringDoesNotSupport($charset)
    {
        $charset = strtolower($charset);
        $charset = str_replace('-', '', $charset);

        $isWindows = (strpos($charset, 'windows') !== false);

        if ($isWindows === false) {
            // This isn't a windows characterset
            return false;
        }

        $windows1251 = (strpos($charset, '1251') !== false);
        $windows1252 = (strpos($charset, '1252') !== false);

        // If Windows but not 1251 and not 1252
        // then it's an upsupported Windows charset
        return  ! $windows1251 && ! $windows1252;
    }

    /**
     * Determine if system has php-imap installed.
     * @return bool
     */
    public function hasImap()
    {
        return function_exists('imap_open');
    }

    /**
     * Determine if php-tidy is installed.
     * @return bool
     */
    public function hasTidy()
    {
        return function_exists('tidy_parse_string');
    }

    /**
     * Determine of the OS is Windows or not
     * (Else assume Linux/Unix).
     * @return bool
     */
    public function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Test if system PHP is between two versions, inclusively.
     *
     * @param string  PHP lower version (php is at least this version)
     * @param string  PHP upper version (php is at most this version)
     * @return bool  PHP is either a lower or upper verions, or between the two
     */
    public function phpBetween($lowerVersion, $upperVersion)
    {
        return  version_compare(PHP_VERSION, $lowerVersion, '>=') && version_compare(PHP_VERSION, $upperVersion, '<=');
    }

    /**
     * Test if PHP is at least this version
     * (if php is the given version or higher).
     *
     * @param string  PHP version
     * @return bool
     */
    public function phpAtLeast($version)
    {
        return version_compare(PHP_VERSION, $version, '>=');
    }

    /**
     * Teste if PHP is at most this version
     * (if php is the given version or lower).
     *
     * @param string  PHP version
     * @return bool
     */
    public function phpAtMost($version)
    {
        return version_compare(PHP_VERSION, $version, '<=');
    }
}
