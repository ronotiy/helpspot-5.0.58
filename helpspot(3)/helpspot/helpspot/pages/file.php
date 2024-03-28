<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************

SERVER OUT A MIME FILE FROM THE
FILEUPLOAD DIRECTORY

use: /admin?pg=file&id=1232
*****************************************/
//clean out any data in the buffer
ob_end_clean();

// Make sure /admin doesn't output any headers
$pagebody = '';
$tab = '';
$subtab = '';

if (app()->runningInConsole()) {
    exit('Files cannot be sent over CLI');
}

$controller = new HS\Http\Controllers\FileController(request());
$response = $controller->downloadFile();

if( hs_setting('IN_PORTAL', false) ) {
    return $response;
}

$response->prepare(request());
$response->send();