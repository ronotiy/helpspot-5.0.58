<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/**
XML callback class
-creates an array of the xml items which can then be used for display
-array key is name and value is value :-)
 */
class livelookup
{
    // array of values in xml file - multi dimension. each index is an array of customer info
    public $customers = [];

    // all tags associated with the current customer being parsed
    public $curCustomer;

    // current tag being parsed
    public $curTag;

    // column attribute array. Use for determining which rows to show.
    public $columns = [];

    // encoding of xml
    public $encoding;

    // tags labels
    public $labels = [];

    public function __construct($encoding = '')
    {
        $this->encoding = $encoding;
    }

    public function start_element($parser, $tag, $attributes)
    {
        if ($tag == 'customer') {	//handle customer tags
            $this->curCustomer = [];
        } elseif ($tag == 'livelookup') {
            //ignore root tag
        } else {	//handle all other tags
            $this->curTag = $tag;
            $this->curCustomer[$this->curTag] = ''; //create key in array
        }

        //handle attributes
        if ($tag == 'livelookup' && is_array($attributes) && ! empty($attributes)) {
            $this->columns = $attributes['columns'];
        } elseif (is_array($attributes) && ! empty($attributes)) {
            $this->labels[$tag] = $attributes['label'];
        }
    }

    public function end_element($parser, $tag)
    {
        if ($tag == 'customer') {	//handle customer tags
            $this->customers[] = $this->curCustomer;
            $this->curCustomer = '';
        } elseif ($tag == 'livelookup') {
            //ignore root tag
        } else {	//handle all other tags
            $this->curTag = '';
        }
    }

    public function data($parser, $data)
    {
        if ($data != '' && ! empty($this->curTag)) {
            $this->curCustomer[$this->curTag] .= $data;
        }
    }

    //TODO: Should stay utf8
    public function getCustomers()
    {
        //If in UTF-8 then convert to HS default encoding
        if ($this->encoding == 'UTF-8') {
            foreach ($this->customers as $key=>$value) {
                foreach ($value as $k=>$v) {
                    $this->customers[$key][$k] = $v;
                }
            }
        }

        return $this->customers;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getLabels()
    {
        return $this->labels;
    }
}
