<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

include_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';

//Don't let the operation timeout
set_time_limit(0);

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.filtermgmt']);
$hidePageFrame = 0;
$pagetitle = lg_admin_filtermgmt_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$feedbackArea = '';
$textoutput = '';

/*****************************************
ACTIONS
*****************************************/

/*****************************************
INFORMATION TABLES
*****************************************/
// number of seconds to consider slow
$slow = 0.2;

$red = function ($value) use ($slow) {
    if ($value > $slow) {
        return "<span style='color:red;'>${value}</span>";
    } else {
        return $value;
    }
};

$columns[] = ['type'=>'link', 'label'=>lg_admin_filtermgmt_filtername, 'sort'=>0, 'width'=>'150', 'fields'=>'xFilter', 'code'=>'<a href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'filter.requests', 'filterid' => '%s', 'xPerson' => '%s'])).'" target="">%s</a>', 'linkfields'=>['xFilter', 'xPerson', 'sFilterName']];
$columns[] = ['type'=>'string', 'label'=>lg_admin_filtermgmt_owner, 'sort'=>0, 'width'=>'80', 'fields'=>'fullname'];
$columns[] = ['type'=>'number', 'label'=>lg_admin_filtermgmt_speedtoday, 'sort'=>0, 'width'=>'50', 'fields'=>'today', 'decimals'=>4, 'function'=>$red];
$columns[] = ['type'=>'string', 'label'=>lg_admin_filtermgmt_views, 'sort'=>0, 'width'=>'20', 'fields'=>'today_ct'];
$columns[] = ['type'=>'number', 'label'=>lg_admin_filtermgmt_speed, 'sort'=>0, 'width'=>'50', 'fields'=>'twoweeks', 'decimals'=>4, 'function'=>$red];
$columns[] = ['type'=>'string', 'label'=>lg_admin_filtermgmt_views, 'sort'=>0, 'width'=>'20', 'fields'=>'twoweek_ct'];
$columns[] = ['type'=>'link', 'label'=>'', 'align-right'=>true, 'sort'=>0, 'width'=>'50', 'fields'=>'xFilter', 'code'=>'<a href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'filter.requests', 'filterid' => '%s', 'delete' => 1])).'" target="">'.hs_htmlspecialchars(lg_admin_filtermgmt_delete).'</a>', 'linkfields'=>['xFilter', 'sFilterName']];

$twoweeks = (time() - (86400 * 14));
$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

// Grab all filters
$filters = $GLOBALS['DB']->GetArray('
	SELECT HS_Filters.xFilter, HS_Filters.sFilterName, HS_Filters.xPerson, '.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' AS fullname
	FROM HS_Filters, HS_Person
	WHERE HS_Filters.xPerson = HS_Person.xPerson
	ORDER BY fullname DESC, sFilterName DESC');

// GET FILTER SPEEDS
$allFilters = [];
foreach ($filters as $filter) {
    $allFilters[$filter['xFilter']] = $filter;
    $allFilters[$filter['xFilter']]['twoweeks'] = $GLOBALS['DB']->GetOne('SELECT AVG(dTime) FROM HS_Filter_Performance WHERE HS_Filter_Performance.xFilter = ? AND dtRunAt > ?', [$filter['xFilter'], $twoweeks]);
    $allFilters[$filter['xFilter']]['twoweek_ct'] = $GLOBALS['DB']->GetOne('SELECT COUNT(xFilter) FROM HS_Filter_Performance WHERE HS_Filter_Performance.xFilter = ? AND dtRunAt > ?', [$filter['xFilter'], $twoweeks]);
    $allFilters[$filter['xFilter']]['today'] = $GLOBALS['DB']->GetOne('SELECT AVG(dTime) FROM HS_Filter_Performance WHERE HS_Filter_Performance.xFilter = ? AND dtRunAt > ?', [$filter['xFilter'], $today]);
    $allFilters[$filter['xFilter']]['today_ct'] = $GLOBALS['DB']->GetOne('SELECT COUNT(xFilter) FROM HS_Filter_Performance WHERE HS_Filter_Performance.xFilter = ? AND dtRunAt > ?', [$filter['xFilter'], $today]);
}

// Get just the slow ones
$slowFilters = [];
foreach ($allFilters as $filter) {
    if ($filter['twoweeks'] > $slow) {
        $slowFilters[$filter['xFilter']] = $filter;
    }
}

$allrs = new array2recordset;
$allrs->init($allFilters);

$problemsrs = new array2recordset;
$problemsrs->init($slowFilters);

// build data table
$problemsTable = recordSetTable($problemsrs, $columns, ['title'=>lg_admin_filtermgmt_problems, 'noresults'=>lg_admin_filtermgmt_noresults], $basepgurl);
$filtersTable = recordSetTable($allrs, $columns, ['title'=>lg_admin_filtermgmt_filters], $basepgurl);

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody = $problemsTable;
$pagebody .= $filtersTable;
