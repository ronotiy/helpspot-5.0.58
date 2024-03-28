<?php
// SECURITY: Don't allow direct calls

if (! defined('cBASEPATH')) {
    die();
}

// This class lets us use a regular array as an adodb recordset
// We can pass this to other functions which expect an adodb recordset
// for display purposes and they'll be able to call FetchRow.
// EXPECTS:  a multidimensional array as input

class array2recordset
{
    public $origArray = [];

    //Helps with adodb compat
    public $databaseType = 'array';

    public $_array;

    public function __construct($ar = [])
    {
        $this->init($ar);
    }

    public function init($ar)
    {
        $this->origArray = $ar;
        $this->_array = &$this->origArray; //For compat with some adodb functions
        $this->Move();
    }

    public function FetchRow()
    {
        // Records are at an end so return false to stop the
        // while loop this method is normally used in
        if (current($this->origArray) === false) {
            return false;
        }

        // Grab the current row and cast to an array which
        // is what all of HelpSpot expects
        $current = (array) current($this->origArray);

        // Advance the array pointer so next time this is called
        // the next element will be returned.
        next($this->origArray);

        return $current;
    }

    public function RecordCount()
    {
        return count($this->origArray);
    }

    public function Move()
    {
        reset($this->origArray);
    }
}
