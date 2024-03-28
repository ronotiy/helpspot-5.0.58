<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('reports');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/class.reports.php';
include cBASEPATH.'/helpspot/lib/class.hscsv.php';

// use with post eventually $get['usebizhours'] = isset($_GET['usebizhours']) ? $_GET['usebizhours'] : 1;

//clean out any data in the buffer
ob_end_clean();
$report = new reports($_POST);

if (isset($_GET['productivity'])) {
    $report->productivity_csv($_GET['show']);
} elseif ($_GET['show'] == 'report_matrix') {
    $report->matrix_csv();
} elseif (isset($_POST['report_list'])) {
    $report->list_create_csv($_GET['show']);
} else {
    $report->create_csv($_GET['show']);
}

// Let's send the file
$report->csv->output();
