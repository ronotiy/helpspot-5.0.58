<?php

/**
 * Provides a simple error class for Savant.
 *
 * $Id: Error.php,v 1.1 2004/10/04 01:52:23 pmjones Exp $
 *
 * @author Paul M. Jones <pmjones@ciaweb.net>
 *
 *
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 */
class Savant2_Error
{
    /**
     * The error code, typically a SAVANT_ERROR_* constant.
     *
     *
     * @var int
     */
    public $code = null;

    /**
     * An array of error-specific information.
     *
     *
     * @var array
     */
    public $info = [];

    /**
     * The error message text.
     *
     *
     * @var string
     */
    public $text = null;

    /**
     * A debug backtrace for the error, if any.
     *
     *
     * @var array
     */
    public $backtrace = null;

    /**
     * Constructor.
     *
     *
     * @param array $conf An associative array where the key is a
     * Savant2_Error property and the value is the value for that
     * property.
     */
    public function Savant2_Error($conf = [])
    {
        // set public properties
        foreach ($conf as $key => $val) {
            $this->$key = $val;
        }

        // generate a backtrace
        if (function_exists('debug_backtrace')) {
            $this->backtrace = debug_backtrace();
        }

        // extended behaviors
        $this->error();
    }

    /**
     * Stub method for extended behaviors.
     *
     *
     * @return void
     */
    public function error()
    {
    }
}
