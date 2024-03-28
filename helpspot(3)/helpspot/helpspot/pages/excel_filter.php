<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.hscsv.php';

// All user filters
$filters = apiGetAllFilters($user['xPerson'], 'all');

//clean out any data in the buffer
ob_end_clean();

$pagebody = '';
$tab = '';
$subtab = '';
$show = isset($_GET['show']) ? trim($_GET['show']) : $user['sWorkspaceDefault'];

switch ($show) {
    case 'inbox':
        if (! perm('fViewInbox')) {
            die();
        }

        $ft = new hs_filter();
        $ft->is_csv = true;
        $ft->useSystemFilter('inbox');

        break;
    case 'myq':
        $ft = new hs_filter();
        $ft->is_csv = true;
        $ft->useSystemFilter('myq');

        break;
    case 'spam':
        if (! perm('fCanManageSpam')) {
            die();
        }
        $ft = new hs_filter();
        $ft->is_csv = true;
        $ft->useSystemFilter('spam');

        break;
    default:
        if (is_numeric($show) && isset($filters[$show])) {
            array_unshift($filters[$show]['displayColumns'], 'view');
            $ft = new hs_filter($filters[$show]);
            $ft->is_csv = true;
        }

        break;
}

if (isset($ft)) {
    // Override sort if needed
    if (! empty($sortby)) {
        $ft->overrideSort = $sortby.' '.$sortord;
    }

    //Add pagination if needed
    //if(isset($_GET['paginate'])) $ft->paginate = $_GET['paginate'];

    //Don't show unread col
    $key = array_search('isunread', $ft->filterDef['displayColumns']);
    if ($key !== false) {
        unset($ft->filterDef['displayColumns'][$key]);
    }

    //Dont show iLastReplyBy col
    $key = array_search('iLastReplyBy', $ft->filterDef['displayColumns']);
    if ($key !== false) {
        unset($ft->filterDef['displayColumns'][$key]);
    }

    //Don't show takeit col
    $key = array_search('takeit', $ft->filterDef['displayColumns']);
    if ($key !== false) {
        unset($ft->filterDef['displayColumns'][$key]);
    }

    $ft->outputExcel();
}
