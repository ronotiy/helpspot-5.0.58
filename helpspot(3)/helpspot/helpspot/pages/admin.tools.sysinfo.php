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
JAVASCRIPT
*****************************************/
$headscript = '';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.sysinfo']);
$hidePageFrame = 0;
$pagetitle = lg_admin_sysinfo_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$feedbackArea = '';
$textoutput = '';

/*****************************************
ACTIONS
*****************************************/
if (isset($_GET['repair'])) {

    // First confirm the table actually exists before attempting to run repair on it.
    // See #607 for the security reason.
    $tables = [];
    $StatusRS = $GLOBALS['DB']->Execute('SHOW TABLE STATUS');
    while ($d = $StatusRS->FetchRow()) {
        $tables[] = $d['Name'];
    }

    if (in_array($_GET['mysql_table'], $tables)) {
        $repair = $GLOBALS['DB']->Execute('REPAIR TABLE '.$_GET['mysql_table']);
        if ($repair) {
            $feedbackArea = displayFeedbackBox(lg_admin_sysinfo_tablerepair);
        }
    }
}

/*****************************************
INFORMATION TABLES
*****************************************/
// System variables
$sysVarsData = $GLOBALS['DB']->Execute('SELECT * FROM HS_Settings WHERE sSetting <> ?', ['cHD_LICENSE']);
// build data table
$sysVarsTable = displayContentBoxTop(lg_admin_sysinfo_systemvars, '', '', '100%', '', 'box-scrolling');

$c = true;
while ($row = $sysVarsData->FetchRow()) {
    $sysVarsTable .= '<div class="yui-g yui-g-row"><div class="yui-u first">'.$row['sSetting'].'</div><div class="yui-u">'.hs_htmlspecialchars(utf8_wordwrap($row['tValue'], '70', "\n", true)).'</div></div>';
}

$sysVarsTable .= displayContentBoxBottom();

// PHP Info get_defined_constants()
$enc = (extension_loaded('ionCube Loader') ? 'ionCube' : 'Zend');
$phpTable = displayContentBoxTop(lg_admin_sysinfo_phpvars.' (php:'.phpversion().' , zend:'.zend_version().') | Encoded With: '.$enc, '', '', '100%', '', 'box-scrolling');
foreach (ini_get_all() as $key=>$value) {
    $phpTable .= '<div class="yui-g yui-g-row"><div class="yui-u first">'.$key.'</div><div class="yui-u">'.hs_htmlspecialchars(utf8_wordwrap($value['global_value'], '70', "\n", true)).'</div></div>';
}
$phpTable .= displayContentBoxBottom();

// Extensions
$extensions = get_loaded_extensions();
array_walk($extensions, function($s){
    return strtolower($s);
});
sort($extensions);
$extTable = displayContentBoxTop(lg_admin_sysinfo_extensions, '', '', '100%', '', 'box-scrolling');
foreach ($extensions as $value) {
    $extTable .= '<div class="yui-g yui-g-row"><div class="yui-u first">'.$value.'</div><div class="yui-u"></div></div>';
}
$extTable .= displayContentBoxBottom();

// Database Info
if (config('database.default') == 'mysql') {
    $StatusRS = $GLOBALS['DB']->Execute('SHOW TABLE STATUS');

    // build data table
    $statusTable = recordSetTable($StatusRS,[['type'=>'string', 'label'=>'Name', 'sort'=>0, 'width'=>'250', 'fields'=>'Name'],
        ['type'=>'string', 'label'=>'Engine', 'sort'=>0, 'fields'=>'Engine'],
        ['type'=>'string', 'label'=>'Rows', 'sort'=>0, 'fields'=>'Rows'],
        ['type'=>'string', 'label'=>'Avg Row Length', 'sort'=>0, 'fields'=>'Avg_row_length'],
        ['type'=>'string', 'label'=>'Data Length', 'sort'=>0, 'fields'=>'Data_length'],
        ['type'=>'string', 'label'=>'Max Data Length', 'sort'=>0, 'fields'=>'Max_data_length'],
        ['type'=>'string', 'label'=>'Data Free', 'sort'=>0, 'fields'=>'Data_free'],
        ['type'=>'string', 'label'=>'Auto Inc', 'sort'=>0, 'fields'=>'Auto_increment'],
        ['type'=>'link', 'label'=>'', 'width'=>'45', 'sort'=>0, 'code'=>'<a href="'.$basepgurl.'&repair=1&mysql_table=%s">Repair</a>', 'fields'=>'Name', 'linkfields'=>['Name']], ],

        ['title'=>lg_admin_sysinfo_hsdb], $basepgurl);

    // Reset Pointer
    $StatusRS->MoveFirst();

    while ($d = $StatusRS->FetchRow()) {
        $textoutput .= $d['Name'].' : '.$d['Engine'].' : '.$d['Rows'].' : '.$d['Avg_row_length'].' : '.$d['Data_length'].' : '.$d['Max_data_length'].' : '.$d['Data_free'].' : '.$d['Auto_increment']."\n";
    }
} else {
    $statusTable = '';
}

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody = '';
if (! empty($feedbackArea)) {
    $pagebody .= $feedbackArea;
}

$pagebody .= '<div class="card padded">';

$pagebody .= $sysVarsTable;

$pagebody .= $phpTable;

$pagebody .= $extTable;

$pagebody .= $statusTable;

$pagebody .= '</div>';
