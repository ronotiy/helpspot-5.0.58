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
class ajaxfield
{
    // array of values in xml file - multi dimension. each index is an array of option info
    public $options = [];

    // all tags associated with the current option being parsed
    public $curOption;

    // current tag being parsed
    public $curTag;

    public function start_element($parser, $tag, $attributes)
    {
        if ($tag == 'option') {	//handle customer tags
            $this->curOption = [];
        } elseif ($tag == 'ajaxfield') {
            //ignore root tag
        } elseif ($tag == 'value') {	//handle all other tags
            $this->curTag = $tag;
            $this->curOption['value'] = ''; //create key in array
        } elseif ($tag == 'description') {	//handle all other tags
            $this->curTag = $tag;
            $this->curOption['description'] = ''; //create key in array
        }
    }

    public function end_element($parser, $tag)
    {
        if ($tag == 'option') {	//handle customer tags
            $this->options[] = $this->curOption;
            $this->curOption = '';
        } elseif ($tag == 'ajaxfield') {
            //ignore root tag
        } else {	//handle all other tags
            $this->curTag = '';
        }
    }

    public function data($parser, $data)
    {
        if (! empty($data) && ! empty($this->curTag)) {
            $this->curOption[$this->curTag] .= $data;
        }
    }

    public function getOptions()
    {
        return $this->options;
    }
}
