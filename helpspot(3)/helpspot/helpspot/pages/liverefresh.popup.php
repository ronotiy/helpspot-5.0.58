<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'liverefresh.popup']);
$hidePageFrame = 1;
$pagetitle = lg_liverefresh_title;
$feedbackArea = '';

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody .= '
<style>
#contents {
	padding-top: 0px;
}
</style>
'.displayContentBoxTop(lg_liverefresh_title);

    $pagebody .= '<p>'.lg_liverefresh_body.'</p>';

$pagebody .= displayContentBoxBottom().'<br>';
