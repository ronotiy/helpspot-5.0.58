<?php

// SECURITY: Don't allow direct calls
use HS\MultiPortal;

if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('admin.tools.portals');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.feeds.php';

$pagetitle = lg_admin_portals;
$tab = 'nav_workspace';

$pagebody = view('portals.index', [
    'portals' => MultiPortal::active()->get()
])->render();
