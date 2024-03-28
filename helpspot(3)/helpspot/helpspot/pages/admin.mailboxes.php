<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('admin.settings');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$pagebody = '';
$basepgurl = route('admin', ['pg' => 'admin.mailboxes']);
$pagetitle = lg_admin_mailboxes_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_mail';
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortord = isset($_GET['sortord']) ? $_GET['sortord'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$resourceid = isset($_GET['resourceid']) && is_numeric($_GET['resourceid']) ? $_GET['resourceid'] : 0;
$showdeleted = isset($_GET['showdeleted']) ? $_GET['showdeleted'] : 0;

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$feedbackArea = '';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$datatable = '';
$delbutton = '';
$overLimit = false;

$fm['sMailbox'] = isset($_POST['sMailbox']) ? $_POST['sMailbox'] : 'INBOX';
$fm['xCategory'] = isset($_POST['xCategory']) ? $_POST['xCategory'] : 0;
$fm['sUsername'] = isset($_POST['sUsername']) ? $_POST['sUsername'] : '';
$fm['sHostname'] = isset($_POST['sHostname']) ? $_POST['sHostname'] : '';
$fm['sPassword'] = isset($_POST['sPassword']) ? $_POST['sPassword'] : '';
$fm['sPasswordConfirm'] = isset($_POST['sPasswordConfirm']) ? $_POST['sPasswordConfirm'] : '';
$fm['sPort'] = isset($_POST['sPort']) ? $_POST['sPort'] : '110';
$fm['sType'] = isset($_POST['sType']) ? $_POST['sType'] : '';
$fm['sSecurity'] = isset($_POST['sSecurity']) ? $_POST['sSecurity'] : '';
$fm['fAutoResponse'] = isset($_POST['fAutoResponse']) ? $_POST['fAutoResponse'] : 0;
$fm['sReplyName'] = isset($_POST['sReplyName']) ? $_POST['sReplyName'] : '';
$fm['sReplyEmail'] = isset($_POST['sReplyEmail']) ? $_POST['sReplyEmail'] : '';
$fm['tAutoResponse'] = isset($_POST['tAutoResponse']) ? $_POST['tAutoResponse'] : lg_admin_mailboxes_msgdefault;
$fm['tAutoResponse_html'] = isset($_POST['tAutoResponse_html']) ? $_POST['tAutoResponse_html'] : lg_admin_mailboxes_msgdefault_html;
$fm['sSMTPSettings_flag'] = isset($_POST['sSMTPSettings_flag']) ? $_POST['sSMTPSettings_flag'] : 'internal';
$fm['fArchive'] = isset($_POST['fArchive']) ? $_POST['fArchive'] : 1;

/*****************************************
PERFORM ACTIONS
*****************************************/
$sorting = getSorting($sortby, $sortord, ['sReplyName', 'sHostname', 'sUsername']);
$data = apiGetAllMailboxes(0, $sorting);
if (! subscription()->canAdd('mailbox', $data->RecordCount())) {
    $overLimit = true;
    $text = '<div style="display:flex;justify-content: space-between;margin: 20px 0;" id="notification-'.$notification->id.'">
                <div>
                    You have reached the free plan mailbox limit. If you need more mailboxes please move to a paid account
                    <a class="action" href="https://store.helpspot.com">buy now</a>
                    or <a class="action" href="https://www.helpspot.com/talk-to-sales">contact sales</a>
                </div>
            </div>';
    $pagebody .= displaySystemBox($text);
}

if ($action == 'add' || $action == 'edit') {
    // add these two items to fm array then pass entire thing in to be processed
    $fm['resourceid'] = $resourceid;
    $fm['mode'] = $action;

    if ($action == 'edit') {
        // Fetch as new variable so it doesn't overwrite existing $fm.
        $fmNew = apiGetMailbox($resourceid);
        $sm = ! hs_empty($fmNew['sSMTPSettings']) ? hs_unserialize($fmNew['sSMTPSettings']) : [];
        $smtpPassword = ($_POST['cHD_MAIL_SMTPPASS'] != '') ? $_POST['cHD_MAIL_SMTPPASS'] : $sm['cHD_MAIL_SMTPPASS'];
    } else {
        $smtpPassword = $_POST['cHD_MAIL_SMTPPASS'];
    }
    //Set custom smtp settings for storage
    if ($fm['sSMTPSettings_flag'] == 'custom') {
        $smtp = [];
        $smtp['cHD_MAIL_SMTPTIMEOUT'] = $_POST['cHD_MAIL_SMTPTIMEOUT'];
        $smtp['cHD_MAIL_SMTPPROTOCOL'] = $_POST['cHD_MAIL_SMTPPROTOCOL'];
        $smtp['cHD_MAIL_SMTPAUTH'] = $_POST['cHD_MAIL_SMTPAUTH'];
        $smtp['cHD_MAIL_SMTPHOST'] = $_POST['cHD_MAIL_SMTPHOST'];
        $smtp['cHD_MAIL_SMTPHELO'] = $_POST['cHD_MAIL_SMTPHELO'];
        $smtp['cHD_MAIL_SMTPUSER'] = $_POST['cHD_MAIL_SMTPUSER'];
        $smtp['cHD_MAIL_SMTPPASS'] = trim($smtpPassword);
        $smtp['cHD_MAIL_SMTPPORT'] = $_POST['cHD_MAIL_SMTPPORT'];
        $fm['sSMTPSettings'] = hs_serialize($smtp);
    } else {
        $fm['sSMTPSettings'] = '';
    }

    $Res = apiAddEditMailbox($fm, __FILE__, __LINE__);
    // if it's an array of errors than skip else continue
    if (! is_array($Res)) {
        $post_res_id = ($action == 'add' ? 0 : $Res);

        //If we've saved the new/edited mailbox correctly let's now save the override email templates
        $templates = hs_unserialize(hs_setting('cHD_EMAIL_TEMPLATES'));
        $templates['mb'.$Res.'_public_subject'] = isset($_POST['mb'.$post_res_id.'_public_subject']) ? trim($_POST['mb'.$post_res_id.'_public_subject']) : '';
        $templates['mb'.$Res.'_public_html'] = isset($_POST['mb'.$post_res_id.'_public_html']) ? trim($_POST['mb'.$post_res_id.'_public_html']) : '';
        $templates['mb'.$Res.'_public'] = isset($_POST['mb'.$post_res_id.'_public']) ? trim($_POST['mb'.$post_res_id.'_public']) : '';
        $templates['mb'.$Res.'_external_subject'] = isset($_POST['mb'.$post_res_id.'_external_subject']) ? trim($_POST['mb'.$post_res_id.'_external_subject']) : '';
        $templates['mb'.$Res.'_external_html'] = isset($_POST['mb'.$post_res_id.'_external_html']) ? trim($_POST['mb'.$post_res_id.'_external_html']) : '';
        $templates['mb'.$Res.'_external'] = isset($_POST['mb'.$post_res_id.'_external']) ? trim($_POST['mb'.$post_res_id.'_external']) : '';
        $templates['mb'.$Res.'_portal_reqcreated_subject'] = isset($_POST['mb'.$post_res_id.'_portal_reqcreated_subject']) ? trim($_POST['mb'.$post_res_id.'_portal_reqcreated_subject']) : '';
        $templates['mb'.$Res.'_portal_reqcreated_html'] = isset($_POST['mb'.$post_res_id.'_portal_reqcreated_html']) ? trim($_POST['mb'.$post_res_id.'_portal_reqcreated_html']) : '';
        $templates['mb'.$Res.'_portal_reqcreated'] = isset($_POST['mb'.$post_res_id.'_portal_reqcreated']) ? trim($_POST['mb'.$post_res_id.'_portal_reqcreated']) : '';
        $templates = hs_serialize($templates);
        storeGlobalVar('cHD_EMAIL_TEMPLATES', $templates);

        //Now redirect with feedback
        $feedback = $resourceid > 0 ? lg_admin_mailboxes_fbedited : lg_admin_mailboxes_fbadded;
        return redirect()
            ->route('admin', ['pg' => 'admin.mailboxes'])
            ->with('feedback', $feedback);
    } else {
        $formerrors = $Res;
        if (empty($formerrors['errorBoxText'])) {
            $formerrors['errorBoxText'] = lg_errorbox;
        }
        setErrors($formerrors);
    }
}

if ($action == 'delete' || $action == 'undelete') {
    $feedback = $action == 'delete' ? lg_admin_mailboxes_fbdeleted : lg_admin_mailboxes_fbundeleted;
    $delCat = apiDeleteResource('HS_Mailboxes', 'xMailbox', $resourceid, $action);
    // Redirect Back
    return redirect()
        ->route('admin', ['pg' => 'admin.mailboxes'])
        ->with('feedback', $feedback);
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/

if (! empty($resourceid)) {

    //Get resource info if there are no form errors. If there was an error then we don't want to get data again
    // that would overwrite any changes the user made
    if (empty($formerrors)) {
        $fm = apiGetMailbox($resourceid);

        //Set settings for SMTP custom connection
        $fm['sSMTPSettings_flag'] = hs_empty($fm['sSMTPSettings']) ? 'internal' : 'custom';
        $sm = ! hs_empty($fm['sSMTPSettings']) ? hs_unserialize($fm['sSMTPSettings']) : [];
    }
    $formaction = 'edit';
    $title = lg_admin_mailboxes_editbox.(! empty($fm['boxname']) ? $fm['boxname'] : '');
    $button = lg_admin_mailboxes_editbutton;
    $showdellink = '';
} elseif ($action == '' || ! empty($formerrors)) {

    // Get category info
    $sorting = getSorting($sortby, $sortord, ['sReplyName', 'sHostname', 'sUsername']);
    $data = apiGetAllMailboxes($showdeleted, $sorting);
    $formaction = 'add';
    $title = lg_admin_mailboxes_addbox;
    $button = lg_admin_mailboxes_addbutton;
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_mailboxes_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_mailboxes_noshowdel.'</a>';
    }

    // build data table
    $datatable = recordSetTable($data,[['type'=>'string', 'label'=>lg_admin_mailboxes_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xMailbox'],
                                            ['type'=>'link', 'label'=>lg_admin_mailboxes_replyname, 'sort'=>1,
                                                    'code'=>'<a href="'.$basepgurl.'&resourceid=%s&showdeleted='.$showdeleted.'">%s</a>',
                                                    'fields'=>'sReplyName', 'linkfields'=>['xMailbox', 'sReplyName'], ],
                                            ['type'=>'string', 'label'=>lg_admin_mailboxes_replyto, 'sort'=>0, 'width'=>'120', 'fields'=>'sReplyEmail'],
                                            ['type'=>'string', 'label'=>lg_admin_mailboxes_mbhost, 'sort'=>1, 'width'=>'120', 'fields'=>'sHostname'],
                                            ['type'=>'string', 'label'=>lg_admin_mailboxes_mbuser, 'sort'=>1, 'width'=>'100', 'fields'=>'sUsername'], ],

                                            ['sortby'=>$sortby,
                                                   'sortord'=>$sortord,
                                                   'noresults'=>lg_admin_mailboxes_nomailboxes,
                                                   'title'=>$pagetitle.$dellable,
                                                   'title_right'=>$showdellink, ], $basepgurl);
}

// dynamic form components
$catsList = apiGetAllCategories(0, '');
$catsSelect = categorySelectOptions($catsList, $fm['xCategory'], '<option value="0">'.lg_admin_mailboxes_nodefault.'</option>');

//if editing a mailbox show delete/restore option
if (! empty($resourceid) && $showdeleted == 0) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_mailboxes_coldelwarn.'\',\''.$basepgurl.'&action=delete&resourceid='.$resourceid.'\');">'.lg_admin_mailboxes_coldel.'</button>';
}
if (! empty($resourceid) && $showdeleted == 1) {
    if ($overLimit) {
        $text = 'You have reached your current mailbox limit. You need to disable an active mailbox before you can restore this one.';
        $delbutton = '<button type="button" class="btn altbtn" name="" onClick="return hs_alert(\''.$text.'\');">'.lg_restore.'</button>';
    } else {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($fm['sUsername'].'@'.$fm['sHostname']).'\',\''.$basepgurl.'&action=undelete&resourceid='.$resourceid.'\');">'.lg_restore.'</button>';
    }
}

$templates = hs_unserialize(hs_setting('cHD_EMAIL_TEMPLATES'));

$pass = (empty($formerrors) && !empty($fm['sPassword'])) ? decrypt($fm['sPassword']) : $fm['sPassword'];

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
	Event.observe(window,"load",function(){
		$$(".tabs").each(function(tabs){
			new Control.Tabs(tabs);
		});

		Event.observe("mailboxform", "submit", function(event){
			if($F("mb'.$resourceid.'_public_html") != "" && !$F("mb'.$resourceid.'_public_subject").include("$tracking_id")){
				hs_alert("'.hs_jshtmlentities(lg_admin_mailboxes_trackidmissing).'");
				Event.stop(event);
			}
		});
	});

	function smtpSwitch(){
		if($F("sSMTPSettings_flag") == "internal"){
			$("smtpblock").hide();
		}else{
			$("smtpblock").show();
		}
	}
</script>
';

if (isHelpspotEmail($fm['sReplyEmail'])) {
    $headscript .= '
        <script type="text/javascript" language="JavaScript">
        $jq().ready(function(){
            $jq(".test-mailbox-container").remove();
        });
        </script>';
} else {
    $headscript .= '
		<script type="text/javascript" language="JavaScript">
		function test_box(){
			$("test_mailbox_results").innerHTML = ajaxLoading("'.hs_jshtmlentities(lg_admin_mailboxes_testing).'");

			//Reset if previously shown
			$("secure_note").hide();

			var url  = "admin";
			var p = ($F("sPassword") == "") ? "'.$pass.'" : $F("sPassword");
			var pars = "pg=ajax_gateway&action=test_mailbox&sType="+$F("sType")+"&sUsername="+$F("sUsername")+"&sPassword="+encodeURIComponent(p)+"&sHostname="+$F("sHostname")+"&sPort="+$F("sPort")+"&sMailbox="+$F("sMailbox")+"&sSecurity="+$F("sSecurity")+"&rand=" + ajaxRandomString();

			var call = new Ajax.Request(
				url,
				{
					method: 	 "get",
					parameters:  pars,
					onComplete:  function(){
						$("test_mailbox_results").innerHTML = arguments[0].responseText;

						//If certificate in mentioned then show the extra note
						if(arguments[0].responseText.indexOf("certificate") > 0){
							$("secure_note").show();
						}
					}
				});

			return false;
		}
		</script>
	';
}

$onload = 'smtpSwitch();';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

// Advanced SMTP Security Options
$listedTransports = smtpSecurityProtocols();
$advancedTransports = '';
if (count($listedTransports)) {
    $advancedTransports = '<optgroup label="'.lg_admin_settings_smtpprotolabeladvanced.'">';
    foreach ($listedTransports as $transport) {
        $advancedTransports .= '<option value="'.$transport.'" '.selectionCheck($transport, $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.$transport.'</option>';
    }
    $advancedTransports .= '</optgroup>';
} else {
    $advancedTransports = '<optgroup label="'.lg_admin_settings_smtpprotolabeladvanced.'">
	<option disabled>None Available</option>
</optgroup>';
}

if (function_exists('imap_open')) {

    // Show the main list of mailboxes.
    $pagebody .= $datatable;

    if ($formaction == 'edit' or ! $overLimit) {
        $pagebody .= '
<form action="'.$basepgurl.'&action='.$formaction.'&resourceid='.$resourceid.'" method="POST" name="mailboxform" id="mailboxform" onSubmit="">
'.csrf_field().'
'.$feedbackArea.'
'. renderInnerPageheader($title, lg_admin_mailboxes_help). '

    <div class="card padded">

    <div class="fr">
        <div class="label">
            <label for="sReplyName" class="datalabel req">'.lg_admin_mailboxes_replyname.'</label>
            <div class="info">'.lg_admin_mailboxes_replynamenote.'</div>
        </div>
        <div class="control">
            <div class="group">
                <input name="sReplyName" id="sReplyName" type="text" size="40" class="'.errorClass('sReplyName').'" value="'.formClean($fm['sReplyName']).'" style="margin-right: 8px;">
					<select id="sReplyName_tag_select" onchange="insertAtCursor($(\'sReplyName\'), $F(\'sReplyName_tag_select\'));$(\'sReplyName_tag_select\').selectedIndex=0;">
						<option value="">'.lg_insertplaceholderopt.'</option>
						<option value="{{ $assigned_first }}">'.lg_placeholderspopup_assignedfirst.'</option>
						<option value="{{ $assigned_first . $assigned_last }}">'.lg_placeholderspopup_assignedfull.'</option>
						<option value="{{ $assigned_last . \', \' . $assigned_first }}">'.lg_placeholderspopup_assignedlastfirst.'</option>
						<option value="{{ $logged_in_first }}">'.lg_placeholderspopup_loggedinfirst.'</option>
						<option value="{{ $logged_in_first . $logged_in_last }}">'.lg_placeholderspopup_loggedinfull.'</option>
						<option value="{{ $logged_in_last . \', \' . $logged_in_first }}">'.lg_placeholderspopup_loggedinlastfirst. '</option>
                    </select>
            </div>
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sReplyEmail" class="datalabel req">'.lg_admin_mailboxes_replyemail.'</label>
            <div class="info">'.lg_admin_mailboxes_replyemailnote.'</div>
        </div>
        <div class="control">
            <input name="sReplyEmail" id="sReplyEmail" type="text" value="'.formClean($fm['sReplyEmail']).'" class="'.errorClass('sReplyEmail').'">
				'.errorMessage('sReplyEmail').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label ">
            <label for="sMailbox" class="datalabel req">'.lg_admin_mailboxes_mailbox.'</label>
            <div class="info">'.lg_admin_mailboxes_mailboxnote.'</div>
        </div>
        <div class="control">
            <input name="sMailbox" id="sMailbox" type="text" size="30" value="'.formClean($fm['sMailbox']).'" class="'.errorClass('sMailbox').'">
				'.errorMessage('sMailbox').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label tdlcheckbox">
            <label for="fArchive" class="datalabel req">'.lg_admin_mailboxes_archive.'</label>
            <div class="info">
                '.lg_admin_mailboxes_archive_note.'
                <div style="font-weight:bold; margin-top: 6px;">'.lg_admin_mailboxes_deletemsg.'</div>
            </div>
        </div>
        <div class="control">
            <input type="checkbox" name="fArchive" id="fArchive" class="checkbox" value="1" '.checkboxCheck(1, $fm['fArchive']) .'>
                <label for="fArchive" class="switch"></label>
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sHostname" class="datalabel req">'.lg_admin_mailboxes_mbhost.'</label>
            <div class="info">'.lg_admin_mailboxes_mbhostnote.'</div>
        </div>
        <div class="control">
            <input name="sHostname" id="sHostname" type="text" size="30" value="'.formClean($fm['sHostname']).'" class="'.errorClass('sHostname').'">
				'.errorMessage('sHostname').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sUsername" class="datalabel req">'.lg_admin_mailboxes_mbuser.'</label>
            <div class="info">'.lg_admin_mailboxes_mbusernote.'</div>
        </div>
        <div class="control">
            <input  name="sUsername" id="sUsername" type="text" size="30" value="'.formClean($fm['sUsername']).'" class="'.errorClass('sUsername').'">
				'.errorMessage('sUsername').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sPassword" class="datalabel req">'.lg_admin_mailboxes_mbpass.'</label>
            <div class="info">'.lg_admin_mailboxes_mbpassnote.'</div>
        </div>
        <div class="control">
            <input name="sPassword" id="sPassword" type="password" size="30" value="" class="'.errorClass('sPassword').'">
				'.errorMessage('sPassword').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sPasswordConfirm" class="datalabel req">'.lg_admin_mailboxes_mbpass_confirm.'</label>
            <div class="info">'.lg_admin_mailboxes_mbpassnote_confirm.'</div>
        </div>
        <div class="control">
            <input name="sPasswordConfirm" id="sPasswordConfirm" type="password" size="30" value="" class="'.errorClass('sPasswordConfirm').'">
				'.errorMessage('sPasswordConfirm').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sType" class="datalabel req">'.lg_admin_mailboxes_mbtype.'</label>
            <div class="info">'.lg_admin_mailboxes_mbtypenote.'</div>
        </div>
        <div class="control">
            <select name="sType" id="sType" class="'.errorClass('sType').'">
                    <optgroup label="'.utf8_ucfirst(lg_admin_mailboxes_recommended).'">
                        <option value="imap" '.selectionCheck('imap', $fm['sType']).'>IMAP</option>
                        <option value="imaps" '.selectionCheck('imaps', $fm['sType']).'>IMAPS '.lg_admin_mailboxes_secure.'</option>
                    </optgroup>
                    <optgroup label="'.utf8_ucfirst(lg_admin_mailboxes_depreciated).'">
                        <option style="color:#ccc;" value="pop3" '.selectionCheck('pop3', $fm['sType']).'>POP3</option>
                        <option style="color:#ccc;" value="pop3s" '.selectionCheck('pop3s', $fm['sType']).'>POP3S '.lg_admin_mailboxes_secure.'</option>
                        <option style="color:#ccc;" value="nntp" '.selectionCheck('nntp', $fm['sType']).'>NNTP</option>
                        <option style="color:#ccc;" value="nntps" '.selectionCheck('nntps', $fm['sType']).'>NNTPS '.lg_admin_mailboxes_secure.'</option>
                    </optgroup>
				</select>
				'.errorMessage('sType').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sPort" class="datalabel req">'.lg_admin_mailboxes_mbport.'</label>
            <div class="info">'.lg_admin_mailboxes_mbportnote.'</div>
        </div>
        <div class="control">
            <input name="sPort" id="sPort" type="text" size="10" value="'.$fm['sPort'].'" class="'.errorClass('sPort').'">
				'.errorMessage('sPort').'
        </div>
    </div>

    <div class="hr"></div>

	<div class="fr">
        <div class="label">
            <label for="sSecurity" class="datalabel">'.lg_admin_mailboxes_mbsecurity.'</label>
            <div class="info">'.lg_admin_mailboxes_mbsecurityex.'</div>
        </div>
        <div class="control">
            <select name="sSecurity" id="sSecurity" class="'.errorClass('sSecurity').'">
                <option value=""></option>
                <option value="novalidate-cert" '.selectionCheck('novalidate-cert', $fm['sSecurity']).'>SSL no-validate</option>
                <option value="tls/novalidate-cert" '.selectionCheck('tls/novalidate-cert', $fm['sSecurity']).'>TLS no-validate</option>
                <option value="tls" '.selectionCheck('tls', $fm['sSecurity']).'>TLS</option>
                <option value="notls" '.selectionCheck('notls', $fm['sSecurity']).'>NOTLS</option>
            </select> '.errorMessage('sSecurity').'
        </div>
    </div>

	'.displayContentBoxTop(lg_admin_mailboxes_testmailbox, lg_admin_mailboxes_testmailboxex, '', '100%', 'test-mailbox-container').'

		<div id="test_mailbox_results" style="width:100%; margin: 0 0 20px 0;"></div>
		<div id="secure_note" style="display:none;font-size:93%;">
			<b>'.lg_admin_mailboxes_testnotesecure.'</b>
			<ul>
				<li>'.lg_admin_mailboxes_testnotepop.'</li>
				<li>'.lg_admin_mailboxes_testnoteimap.'</li>
			</ul>
		</div>
		<button type="button" name="test_mailbox" class="btn accent" id="test_mailbox" onclick="test_box();" >'.lg_admin_mailboxes_testmailbox.'</button>

	'.displayContentBoxBottom().'

	<fieldset class="fieldset">
		<div class="sectionhead">'.lg_admin_mailboxes_options.'</div>

		<div class="fr">
            <div class="label">
                <label for="xCategory" class="datalabel">'.lg_admin_mailboxes_defcat.'</label>
                <div class="info">'.lg_admin_mailboxes_defcatnote.'</div>
            </div>
            <div class="control">
                <select name="xCategory" id="xCategory" class="'.errorClass('xCategory').'">'.$catsSelect.'</select> <br>
				'.errorMessage('xCategory').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="fAutoResponse" class="datalabel">'.lg_admin_mailboxes_enablear.'</label>
                <div class="info">'.lg_admin_mailboxes_enablearnote.'</div>
            </div>
            <div class="control">
                <select name="fAutoResponse" id="fAutoResponse" class="'.errorClass('xCategory').'">
					<option value="1" '.selectionCheck('1', $fm['fAutoResponse']).'>'.lg_enable.'</option>
					<option value="0" '.selectionCheck('0', $fm['fAutoResponse']).'>'.lg_disable.'</option>
				</select>
            </div>
        </div>

	</fieldset>

	<fieldset class="fieldset">
		<div class="sectionhead">'.lg_admin_mailboxes_outbound.'</div>

		<div class="fr">
            <div class="label">
                <label for="sSMTPSettings_flag" class="datalabel">'.lg_admin_mailboxes_outbounduse.'</label>
                <div class="info">'.lg_admin_mailboxes_outboundex.'</div>
            </div>
            <div class="control">
                <select name="sSMTPSettings_flag" id="sSMTPSettings_flag" onchange="smtpSwitch();" tabindex="107">
					<option value="internal" '.selectionCheck('internal', $fm['sSMTPSettings_flag']).'>'.lg_admin_mailboxes_outboundinternal.'</option>
					<option value="custom" '.selectionCheck('custom', $fm['sSMTPSettings_flag']).'>'.lg_admin_mailboxes_outboundcustom.'</option>
				</select>
            </div>
        </div>

		<div class="ft" id="smtpblock" style="display:none;">

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="datalabel" for="cHD_MAIL_SMTPPROTOCOL">'.lg_admin_settings_smtpproto.'</label>
                    <div class="info">'.lg_admin_settings_smtpprotoex.'</div>
                </div>
                <div class="control">';
                    if (defined('cEXTRASECURITYOPTIONS')) {
                        $pagebody .= '<select name="cHD_MAIL_SMTPPROTOCOL">
                                <option value="" '.selectionCheck('', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprotonone.'</option>
                                <optgroup label="'.lg_admin_settings_smtpprotolabeldefault.'">
                                    <option value="ssl" '.selectionCheck('ssl', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprotossl.'</option>
                                    <option value="tls" '.selectionCheck('tls', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprototls.'</option>
                                </optgroup>
                            '.$advancedTransports.'
                            </select>';
                    } else {
                        $pagebody .= '<select name="cHD_MAIL_SMTPPROTOCOL">
                                <option value="" '.selectionCheck('', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprotonone.'</option>
                                <option value="ssl" '.selectionCheck('ssl', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprotossl.'</option>
                                <option value="tls" '.selectionCheck('tls', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprototls.'</option>
                                <option value="tlsv1.2" '.selectionCheck('tlsv1.2', $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.lg_admin_settings_smtpprototlsv12.'</option>
                            </select>';
                    }

                    $pagebody .= '
                        </div>
                    </div>

                    <div class="hr"></div>

                    <div class="fr">
                        <div class="label">
                            <label class="datalabel" for="cHD_MAIL_SMTPHOST">'.lg_admin_settings_smtphost.'</label>
                            <div class="info">'.lg_admin_settings_smtphostex.'</div>
                        </div>
                        <div class="control">
                            <input name="cHD_MAIL_SMTPHOST" id="cHD_MAIL_SMTPHOST" type="text" size="40" value="'.formClean($sm['cHD_MAIL_SMTPHOST']).'">
                        </div>
                    </div>

                    <div class="hr"></div>

                    <div class="fr">
                        <div class="label"><label class="datalabel" for="cHD_MAIL_SMTPPORT">'.lg_admin_settings_smtpport.'</label></div>
                        <div class="control">
                            <input name="cHD_MAIL_SMTPPORT" id="cHD_MAIL_SMTPPORT" type="text" size="10" value="'.formClean($sm['cHD_MAIL_SMTPPORT']).'">
                        </div>
                    </div>

                    <div class="hr"></div>

                    <div class="fr">
                        <div class="label"><label class="datalabel" for="cHD_MAIL_SMTPAUTH">'.lg_admin_settings_smtpauth.'</label></div>
                        <div class="control">
                            <select name="cHD_MAIL_SMTPAUTH">
                                <option value="1" '.selectionCheck('1', $sm['cHD_MAIL_SMTPAUTH']).'>'.lg_yes.'</option>
                                <option value="0" '.selectionCheck('0', $sm['cHD_MAIL_SMTPAUTH']).'>'.lg_no.'</option>
                            </select>
                        </div>
                    </div>

                    <div class="hr"></div>


                    <div class="fr">
                        <div class="label"><label class="datalabel" for="cHD_MAIL_SMTPUSER">'.lg_admin_settings_smtpuser.'</label></div>
                        <div class="control">
                            <input name="cHD_MAIL_SMTPUSER" id="cHD_MAIL_SMTPUSER" type="text" size="40" value="'.formClean($sm['cHD_MAIL_SMTPUSER']).'">
                        </div>
                    </div>

                    <div class="hr"></div>

                    <div class="fr">
                        <div class="label">
                            <label class="datalabel" for="cHD_MAIL_SMTPPASS">'.lg_admin_settings_smtppass.'</label>
                            <div class="info">'.lg_admin_mailboxes_smtppass_msg.'</div>
                        </div>
                        <div class="control">
                            <input name="cHD_MAIL_SMTPPASS" id="cHD_MAIL_SMTPPASS" type="password" size="40" value="">
                        </div>
                    </div>

                    <div class="hr"></div>

                    <div class="fr">
                        <div class="label"><label class="datalabel" for="cHD_MAIL_SMTPTIMEOUT">'.lg_admin_settings_smtptimeout.'</label></div>
                        <div class="control">
                            <input name="cHD_MAIL_SMTPTIMEOUT" id="cHD_MAIL_SMTPTIMEOUT" type="text" size="40" value="'.(($sm['cHD_MAIL_SMTPTIMEOUT']) ? formClean($sm['cHD_MAIL_SMTPTIMEOUT']) : 10).'">
                        </div>
                    </div>

                    <div class="hr"></div>

                    <div class="fr">
                        <div class="label">
                            <label class="datalabel" for="cHD_MAIL_SMTPHELO">'.lg_admin_settings_smtphelo.'</label>
                            <div class="info">'.lg_admin_settings_smtpheloex.'</div>
                        </div>
                        <div class="control">
                            <input name="cHD_MAIL_SMTPHELO" id="cHD_MAIL_SMTPHELO" type="text" size="40" value="'.formClean($sm['cHD_MAIL_SMTPHELO']).'">
                        </div>
                    </div>

        </div>

	</fieldset>

	'.displayContentBoxTop(lg_admin_mailboxes_emailtemplates, lg_admin_mailboxes_msgnote).'


			<div class="fr">
				<div class="label"><label class="datalabel">'.lg_admin_mailboxes_etar.'</label></div>
				<div class="control">
					'.editEmailTemplate(false, false, $fm, 'tAutoResponse', lg_admin_mailboxes_etar, $resourceid).'
					'.errorMessage('tAutoResponse').'
					'.errorMessage('tAutoResponse_html').'
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label"><label class="datalabel">'.lg_admin_mailboxes_etpublic.'</label></div>
				<div class="control">
					'.editEmailTemplate(true, 'public', $templates, 'mb'.$resourceid.'_public', lg_admin_mailboxes_etpublic, $resourceid,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}'     => lg_placeholderspopup_requestcheckurl,
                            '{{ $accesskey }}'           => lg_placeholderspopup_accesskey,
                            '{{ $message }}'             => lg_placeholderspopup_message,
                            '{{ $fullpublichistoryex }}' => lg_placeholderspopup_fullpublichistory_ex,
                            '{{ $fullpublichistory }}'   => lg_placeholderspopup_fullpublichistory,
                            '{{ $lastcustomernote }}'    => lg_placeholderspopup_lastcustomernote,
                        ]
                ).'
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label"><label class="datalabel">'.lg_admin_mailboxes_etexternal.'</label></div>
				<div class="control">
					'.editEmailTemplate(true, 'external', $templates, 'mb'.$resourceid.'_external', lg_admin_mailboxes_etexternal, $resourceid,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}'   => lg_placeholderspopup_requestcheckurl,
                            '{{ $accesskey }}'         => lg_placeholderspopup_accesskey,
                            '{{ $message }}'           => lg_placeholderspopup_message,
                            '{{ $fullpublichistory }}' => lg_placeholderspopup_fullpublichistory,
                        ]
                ).'
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label">
                    <label class="datalabel">'.lg_admin_mailboxes_etreqcreatedbyform.'</label>
                    <div class="info">'.lg_admin_mailboxes_etreqcreatedbyform_note.'</div>
                </div>
				<div class="control">
					'.editEmailTemplate(true, 'portal_reqcreated', $templates, 'mb'.$resourceid.'_portal_reqcreated', lg_admin_mailboxes_etreqcreatedbyform, $resourceid,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}' => lg_placeholderspopup_requestcheckurl,
                            '{{ $accesskey }}'       => lg_placeholderspopup_accesskey,
                            '{{ $message }}'         => lg_placeholderspopup_message, ]
                ).'
				</div>
			</div>


	'.displayContentBoxBottom().'

	<input type="hidden" name="sampleSubject" value="'.lg_admin_mailboxes_samplesubject.'">

    </div>

	<div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">'.$button.'</button>'.$delbutton. '
    </div>

</form>';
    }
} else {
    $pagebody = errorBox(lg_admin_mailboxes_noimap, '700');
}
