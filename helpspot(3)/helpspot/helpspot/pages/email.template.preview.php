<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('admin.mailboxes');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'email.template.preview']);
$htmldirect = true;
$pagetitle = '';
$show = $_GET['template'];
$type = $_GET['type'];

/*****************************************
FIND PROPER EMAIL TEMPLATE
*****************************************/
$templates = hs_unserialize(hs_setting('cHD_EMAIL_TEMPLATES'));

//Mailbox auto reply
if (isset($_GET['xMailbox']) && $_GET['xMailbox'] > 0 && ! isset($templates[$show])) {
    $mailbox = apiGetMailbox($_GET['xMailbox']);

    $t = $mailbox[$show];

    if ($type != 'html') {
        $t = nl2br($t);
    }

    //Custom mailbox templates
} elseif (isset($templates[$show]) && ! empty($templates[$show])) {
    $t = $templates[$show];

    if ($type != 'html') {
        $t = nl2br($t);
    }

    //Mailbox auto reply for new mailbox
} elseif (isset($_GET['xMailbox']) && $_GET['xMailbox'] == 0 && ($show == 'tAutoResponse' || $show == 'tAutoResponse_html')) {
    if ($show == 'tAutoResponse') {
        $t = nl2br(lg_admin_mailboxes_msgdefault);
    } else {
        $t = lg_admin_mailboxes_msgdefault_html;
    }

    //No custom template available
} else {
    $t = '<div style="color:#222;font-size:14px;margin:2%;margin-top:180px;width:92%;text-align:center;background-color:#CACACA;padding:2%;-webkit-border-radius: 6px;-moz-border-radius: 6px;border-radius: 6px;">
		'.lg_templatepreview_nosaved.'
	</div>';
}

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody .= $t;
