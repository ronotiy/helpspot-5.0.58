<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$name = str_replace(['_', '.php'], [' ', ''], $_GET['file']);
$name = utf8_ucwords($name);

$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'custompg']);
$pagetitle = $name;
$htmldirect = 0;
$tab = 'nav_workspace';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody = $custom_pagebody;
