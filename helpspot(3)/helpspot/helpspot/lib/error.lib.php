<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

set_error_handler('error_handler');

/*****************************************
LOG ERRORS
*****************************************/
function errorLog($errordesc = '', $type = '', $filename = '', $line = '')
{
    //Check if file or line is null for some reason and fix
    if (! $filename) {
        $filename = '';
    }
    if (! $line) {
        $line = '';
    }
    $errordesc = utf8_substr($errordesc, 0, 255); // Truncate at 255 characters

    //IF is a hack to avoid this error being logged a ton on some systems. This needs to be fixed in v2.
    if (strpos($errordesc, 'SELECT DISTINCT HS_Request.*') === false && is_object($GLOBALS['DB'])) {
        $GLOBALS['DB']->Execute('INSERT INTO HS_Errors(dtErrorDate,sType,sFile,sLine,sDesc) VALUES (?,?,?,?,?)',
                                                                    [date('U'), $type, $filename, $line, $errordesc]);
    }

    /*
    if(defined('cHD_CUSTCONNECT_ACTIVE') && cHD_CUSTCONNECT_ACTIVE)
    {

    }
    */

    return true;
}

/**
 * Given an error message or blank string, returns the name of a
 * class to be used for an edit box.
 * @param $name
 * @return string
 */
function errorClass($name)
{
    if (app('form.errors')->get($name)) {
        return 'hdformerror';
    }

    return 'hdform';
}

/**
 * Set the global errors.
 *
 * @param array $errors
 * @return mixed
 */
function setErrors(array $errors)
{
    return app('form.errors')->set($errors);
}

/**
 * Displays an error message in a dialog box.
 * @param $name
 * @return string
 */
function errorMessage($name)
{
    $error = app('form.errors')->get($name);
    if ($error) {
        return	' <span class="hderrorlabel">'.$error.'</span>';
    }

    return '';
}

// GLOBAL ERROR HANDLER
// put error in global and then insert into db later if possible using errorCleanup().

function error_handler($type, $message, $file = __FILE__, $line = __LINE__)
{
    switch ($type) {
    case E_USER_ERROR:
       print 'An error has occurred. This error has been logged, you may want to try the back button to return.';
      exit;

    break;
    case E_USER_WARNING:
    case E_USER_NOTICE:
        @$GLOBALS['PHP_ERROR_STACK'][] = [$type, $message, $file, $line];

    break;
    default:
    break;
    }
}

function errorCleanup()
{
    if (! empty($GLOBALS['PHP_ERROR_STACK'])) {
        foreach ($GLOBALS['PHP_ERROR_STACK'] as $er) {
            errorLog($er[0].' : '.$er[1], 'PHP', $er[2], $er[3]);
        }
    }
}

//handles errors by adodb
function adodbErrors($msg, $newlines)
{
    errorLog($msg, 'ADODB', '', '');
}
