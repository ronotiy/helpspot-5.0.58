<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
lineend - return correct line ending for
string
*****************************************/
function lineEnd()
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $out = "\r\n";
    } else {
        $out = "\n";
    }

    return $out;
}

/*****************************************
sep - string replace for correct sep.
not implemented yet. see if needed on win
*****************************************/
function sep($path)
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return str_replace('/', '\\', $path);
    } else {
        return $path;
    }
}
