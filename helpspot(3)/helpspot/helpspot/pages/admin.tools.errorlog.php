<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

//Don't let the operation timeout
set_time_limit(0);

/*****************************************
LIBS
*****************************************/

/*****************************************
PERFORM ACTIONS
*****************************************/
if (isset($_POST['submit'])) {
    $GLOBALS['DB']->Execute('DELETE FROM HS_Errors');
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.errorlog']);
$hidePageFrame = 0;
$pagetitle = lg_admin_errorlog_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$offset = 4000;
$bottomlinks = '';
$pages = '';
$start = isset($_GET['start']) ? $_GET['start'] : '0';
$pagelen = 60;

$rs = $GLOBALS['DB']->SelectLimit('SELECT * FROM HS_Errors ORDER BY dtErrorDate DESC', $pagelen, $start);

//BUILD PAGE LINKS
if ($start != 0) {
    $pages .= '<a href="'.$basepgurl.'&start='.($start - $pagelen - 1).'">'.lg_prevpage.'</a>';
}
if ($start != 0 && ($rs->RecordCount() == $pagelen)) {
    $pages .= ' | ';
}
if ($rs->RecordCount() == $pagelen) {
    $pages .= '<a href="'.$basepgurl.'&start='.($start + $pagelen + 1).'">'.lg_nextpage.'</a>';
}
//END PAGE LINKS

// filter
$rs->Walk(function (&$item) {
    $item = (array) $item;
    $item['sFile'] = basename($item['sFile']);
    $item['dtErrorDate'] = hs_showCustomDate($item['dtErrorDate'], '%a, %b %e, %Y, %I:%M %p');
});

// build data table
$logTable = recordSetTable($rs,[['type'=>'string', 'label'=>lg_admin_errorlog_time, 'width'=>'180', 'sort'=>0, 'fields'=>'dtErrorDate'],
                                              ['type'=>'string', 'label'=>lg_admin_errorlog_type, 'width'=>'120', 'sort'=>0, 'fields'=>'sType'],
                                              ['type'=>'string', 'label'=>lg_admin_errorlog_msg, 'sort'=>0, 'fields'=>'sDesc'],
                                              ['type'=>'string', 'label'=>lg_admin_errorlog_file, 'width'=>'150', 'sort'=>0, 'fields'=>'sFile'],
                                              ['type'=>'string', 'label'=>lg_admin_errorlog_line, 'width'=>'50', 'sort'=>0, 'fields'=>'sLine'], ],
                                              ['title'=>lg_admin_errorlog_title,
                                                    'rightfooter'=>$pages,
                                                    'title_right'=> '<button type="submit" name="submit" class="btn" style="margin-top:8px;">'.lg_admin_errorlog_clear.'</button>',
                                                    'width'=>'100%', ], $basepgurl);

/*****************************************
PAGE OUTPUTS
****************************************/
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

$pagebody .= '<form action="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.errorlog']).'" method="post">';
$pagebody .= csrf_field();
$pagebody .= $logTable;
$pagebody .= '</form>';
