<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'shortcuts.popup']);
$hidePageFrame = 1;
$pagetitle = lg_shortcutspopup_title;
$feedbackArea = '';

$filters = apiGetAllFilters($user['xPerson'], 'all');

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody .= '
<style>
#contents {
	padding-top: 0px;
}
</style>
'.$feedbackArea.'
'.displayContentBoxTop(lg_shortcutspopup_qkeys);

    $pagebody .= '
	<table>
		<tr><td width="200"><label for="" class="datalabel">'.lg_shortcutspopup_upq.'</label></td><td> <b>'.lg_shortcutspopup_uparrow.'</b> or <b>k</b></td></tr>
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_downq.'</label></td><td> <b>'.lg_shortcutspopup_downarrow.'</b> or <b>j</b></td></tr>
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_viewreq.'</label></td><td> <b>'.lg_shortcutspopup_rightarrow.'</b> or <b>v</b> or <b>o</b></td></tr>
		<!--<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_back.'</label></td><td> <b>'.lg_shortcutspopup_leftarrow.'</b></td></tr>-->
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_check.'</label></td><td> <b>x</b></td></tr>
	</table>';

$pagebody .= displayContentBoxBottom().'<br>';

$pagebody .= displayContentBoxTop(lg_shortcutspopup_fkeys);

    $pagebody .= '
	<table>
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_createrequest.'</label></td><td> <b>c</b></td></tr>
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_searchbox.'</label></td><td> <b>r</b></td></tr>
		<tr><td width="200"><label for="" class="datalabel">'.lg_shortcutspopup_fws.'</label></td><td> <b>1</b></td></tr>
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_finbox.'</label></td><td> <b>2</b></td></tr>
		<tr><td><label for="" class="datalabel">'.lg_shortcutspopup_fqueue.'</label></td><td> <b>3</b></td></tr>
	';

    $foptions = [];
    //First create mapping, then create options
    foreach ($filters as $fk=>$f) {
        if (! hs_empty($f['sShortcut'])) {
            $pagebody .= '<tr><td><label for="" class="datalabel">'.$f['sFilterName'].'</label></td><td> <b>'.$GLOBALS['filterKeys'][$f['sShortcut']].'</b></td></tr>';
        }
    }

    $pagebody .= '</table>';

$pagebody .= displayContentBoxBottom();
