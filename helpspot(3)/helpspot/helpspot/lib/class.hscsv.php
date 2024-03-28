<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class hscsv
{
    //Body of file string we're creating
    public $filestr = '';

    //CSV Title
    public $csvtitle = '';

    //CSV File Values
    public $quote = '"';

    public $sep = "\t";

    public $end_line = "\n";

    public function __construct($title = '')
    {
        $this->setTitle($title);
    }

    public function setTitle($title)
    {
        $this->csvtitle = str_replace([':', '/'], ' ', $title);
    }

    //Logic for writing to file. Must always pass in the columns and an assoc array of values
    public function writeRow($fields, $function = false, $force_quote = false)
    {
        $field_count = count($fields);
        $i = 1;
        //foreach($cols AS $key=>$col){
        foreach ($fields as $k=>$value) {
            //IF there's a function to apply to this field then do so
            /*
            if($function && isset($function[$key])){
                $f 	   = $function[$key];
                $fields[$key] = $f($fields[$key]);
            }
            */

            // Write a single field
            $quote_field = $force_quote;
            // Only quote this field in the following cases:
            if (is_numeric($value)) {
                // Numeric fields should not be quoted
            } elseif (utf8_strpos($value, $this->sep) !== false) {
                // Separator is present in field
                $quote_field = true;
            } elseif (utf8_strpos($value, $this->quote) !== false) {
                // Quote character is present in field
                $quote_field = true;
            } elseif (
                   utf8_strpos($value, "\n") !== false
                || utf8_strpos($value, "\r") !== false
            ) {
                // Newline is present in field
                $quote_field = true;
            } elseif (! is_numeric($value) && (substr($value, 0, 1) == ' ' || substr($value, -1) == ' ')) {
                // Space found at beginning or end of field value
                $quote_field = true;
            }

            if ($quote_field) {
                // Escape the quote character within the field (e.g. " becomes "")
                $quoted_value = str_replace($this->quote, $this->quote.$this->quote, $value);

                $this->filestr .= $this->quote.$quoted_value.$this->quote;
            } else {
                $this->filestr .= $value;
            }

            if ($i < $field_count) {
                $this->filestr .= $this->sep;
            }
            $i++;
        }

        $this->filestr .= $this->end_line;
    }

    public function output()
    {
        // sending HTTP headers
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="'.$this->csvtitle.'.csv"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
        header('Pragma: public');
        //convert to UTF16LE in order to allow excel to read the file properly
        echo chr(255).chr(254).mb_convert_encoding($this->filestr, 'UTF-16LE', 'UTF-8');
    }
}
