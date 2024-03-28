<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.responses']);
$tab = 'nav_responses';
$subtab = 'nav_admin_resp';
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortord = isset($_GET['sortord']) ? $_GET['sortord'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$resourceid = isset($_GET['resourceid']) && is_numeric($_GET['resourceid']) ? $_GET['resourceid'] : 0;
$showdeleted = isset($_GET['showdeleted']) ? $_GET['showdeleted'] : 0;

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$global = isset($_REQUEST['global']) ? $_REQUEST['global'] : 0;
$paginate = isset($_GET['paginate']) && is_numeric($_GET['paginate']) ? $_GET['paginate'] : 0;
$feedbackArea = '';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$datatable = '';
$showdellink = '';
$delbutton = '';
$editor_type = 'none';

$pagetitle = lg_admin_responses_title;

//small search of responses when used in admin.responses will not be able to send showdeleted flag. So check if it's deleted and redirect.
if (empty($action) && ! $showdeleted && $resourceid != 0) {
    $delete_check = $GLOBALS['DB']->GetOne('SELECT fDeleted FROM HS_Responses WHERE xResponse = ?', [$resourceid]);
    if ($delete_check) {
        return redirect()
            ->route('admin', ['pg' => 'admin.responses', 'resourceid' => $resourceid, 'showdeleted' => 1]);
    }	// Redirect Back
}

$fm['sResponseTitle'] = isset($_POST['sResponseTitle']) ? $_POST['sResponseTitle'] : '';
$fm['sFolder'] = isset($_POST['sFolder']) ? $_POST['sFolder'] : '';
$fm['tResponse'] = isset($_POST['tResponse']) ? $_POST['tResponse'] : '';
$fm['sTitle'] = isset($_POST['sTitle']) ? $_POST['sTitle'] : '';
$fm['emailfrom'] = isset($_POST['emailfrom']) ? $_POST['emailfrom'] : '';
$fm['togroup'] = isset($_POST['togroup']) ? $_POST['togroup'] : '';
$fm['ccgroup'] = isset($_POST['ccgroup']) ? $_POST['ccgroup'] : '';
$fm['bccgroup'] = isset($_POST['bccgroup']) ? $_POST['bccgroup'] : '';
$fm['xStatus'] = isset($_POST['xStatus']) ? $_POST['xStatus'] : '';
$fm['xCategory'] = isset($_POST['xCategory']) ? $_POST['xCategory'] : '';
$fm['xPerson'] = isset($_POST['xPerson']) ? $_POST['xPerson'] : $user['xPerson'];
$fm['xReportingTags'] = isset($_POST['xReportingTags']) ? $_POST['xReportingTags'] : '';
$fm['xPersonAssignedTo'] = isset($_POST['xPersonAssignedTo']) ? $_POST['xPersonAssignedTo'] : '';
$fm['fPublic'] = isset($_POST['fPublic']) ? $_POST['fPublic'] : '';
$fm['fType'] = isset($_POST['fType']) ? $_POST['fType'] : 2;
$fm['fPermissionGroup'] = isset($_POST['fPermissionGroup']) ? $_POST['fPermissionGroup'] : 0;
$fm['sPersonList'] = isset($_POST['sPersonList']) ? $_POST['sPersonList'] : [];
$fm['fSendEvery'] = isset($_POST['fSendEvery']) ? $_POST['fSendEvery'] : '';
$fm['fSendDay'] = isset($_POST['fSendDay']) ? $_POST['fSendDay'] : '';
$fm['fSendTime'] = isset($_POST['fSendTime']) ? $_POST['fSendTime'] : '';
$fm['sFirstName'] = isset($_POST['sFirstName']) ? $_POST['sFirstName'] : '';
$fm['sLastName'] = isset($_POST['sLastName']) ? $_POST['sLastName'] : '';
$fm['sEmail'] = isset($_POST['sEmail']) ? $_POST['sEmail'] : '';
$fm['sPhone'] = isset($_POST['sPhone']) ? $_POST['sPhone'] : '';
$fm['sUserId'] = isset($_POST['sUserId']) ? $_POST['sUserId'] : '';
$fm['fRecurringRequest'] = isset($_POST['fRecurringRequest']) ? $_POST['fRecurringRequest'] : '';

// Setup custom fields
if (is_array($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
    foreach ($GLOBALS['customFields'] as $k=>$v) {
        $custid = 'Custom'.$v['fieldID'];
        $fm[$custid] = isset($_POST[$custid]) ? $_POST[$custid] : '';

        //Special code to handle drill down fields
        //Holds each drill down separate so that each can be set by js
        if ($v['fieldType'] == 'drilldown' && isset($_POST[$custid.'_ct'])) {
            $values = [];
            for ($i = 1; $i <= $_POST[$custid.'_ct']; $i++) {
                if (! empty($_POST[$custid.'_'.$i])) {
                    $values[] = $_POST[$custid.'_'.$i];
                }
                $fm[$custid.'_'.$i] = ! empty($_POST[$custid.'_'.$i]) ? $_POST[$custid.'_'.$i] : '';
            }
            $fm[$custid] = implode('#-#', $values);
        }
    }
}

//See if we need to load an editor
if (hs_setting('cHD_HTMLEMAILS')) {
    $editor_type = 'markdown';
}

/*****************************************
FUNCTION TO LABEL TYPE
*****************************************/
function responsePermLabel($type)
{
    if ($type == 1) {
        return lg_everyone;
    } elseif ($type == 2) {
        return lg_admin_responses_typeuser;
    } elseif ($type == 3) {
        return lg_admin_responses_typegroup;
    } else {
        return lg_admin_responses_typeppl;
    }
}

function responseRecurringLabel($type) {
    if ($type == 1) {
        $img = '<img src="'.static_url().'/static/img5/match.svg" alt="" width="16" height="16">';
    } else {
        $img = '<img src="'.static_url().'/static/img5/circle-white-solid.svg" alt="" width="16" height="16">';
    }
    return '<div style="text-align: center;">'. $img .'</div>';
}

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($action == 'add' || $action == 'edit') {
    //Do overrides for Save As
    if (isset($_POST['saveAs_sResponseTitle']) && ! empty($_POST['saveAs_sResponseTitle'])) {
        $action = 'add';
        $resourceid = 0;
        $fm['sResponseTitle'] = $_POST['saveAs_sResponseTitle'];
    }

    // add these two items to fm array then pass entire thing in to be processed
    $fm['resourceid'] = $resourceid;
    $fm['mode'] = $action;

    $Res = apiAddEditResponse($fm, $_FILES, __FILE__, __LINE__);
    // if it's an array of errors than skip else continue
    if (! is_array($Res)) {
        $feedback = ($resourceid == 0) ? lg_admin_responses_fbadded : lg_admin_responses_fbedited;
        return redirect()
            ->route('admin', ['pg' => 'admin.responses'])
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
    $feedback = $action == 'delete' ? lg_admin_responses_setinactive : lg_admin_responses_setactive;
    $delCat = apiDeleteResource('HS_Responses', 'xResponse', $resourceid, $action);
    // Redirect Back
    return redirect()
        ->route('admin', ['pg' => 'admin.responses'])
        ->with('feedback', $feedback);
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/

if (! empty($resourceid)) {

    //Get resource info if there are no form errors. If there was an error then we don't want to get data again
    // that would overwrite any changes the user made
    if (empty($formerrors)) {
        $fm = apiGetRequestResponse($resourceid);
        //put json advanced options in fm array
        if (! hs_empty($fm['tResponseOptions'])) {
            $options = json_decode($fm['tResponseOptions'], true);
            $fm = array_merge($fm, $options);
        }
    }

    //Determine if any actions are set which require boxes to be open
    if (! empty($fm['xStatus']) || ! empty($fm['xCategory']) || ! empty($fm['xPersonAssignedTo'])) {
        $toggle_request_details = true;
    } else {
        $toggle_request_details = false;
    }

    $toggle_custom_fields = false;
    foreach ($fm as $k=>$v) {
        if (substr($k, 0, 6) == 'Custom' && ! empty($v)) {
            $toggle_custom_fields = true;
        }
    }

    if (! empty($fm['sTitle']) || ! empty($fm['fPublic']) || ! empty($fm['emailfrom']) || ! empty($fm['togroup']) || ! empty($fm['ccgroup']) || ! empty($fm['bccgroup'])) {
        $toggle_note_options = true;
    } else {
        $toggle_note_options = false;
    }

    //check that user has access to this resource
    if ($fm['xPerson'] != $user['xPerson'] && ! isAdmin()) {
        exit();
    }

    $formaction = 'edit';
    $title = lg_admin_responses_edit.formCleanHtml($fm['sResponseTitle']);
    $button = lg_admin_responses_editbutton;
    $showdellink = '';

    $secondary_button = save_as_button(lg_saveas, lg_admin_responses_saveas_details, 'saveAs_sResponseTitle', 'sResponseTitle');
} elseif ($action == '' || ! empty($formerrors)) {

    // Get category info
    $data = apiGetAllRequestResponses($showdeleted, $user['xPerson'], $user['fUserType'], true, ($sortby . ' ' . $sortord));
    $formaction = 'add';
    $title = lg_admin_responses_add;
    $button = lg_admin_responses_addbutton;
    $secondary_button = '';

    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_responses_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_responses_noshowdel.'</a>';
    }

    // build data table
    $ar = [];
    $ar[] = ['type'=>'link', 'label'=>lg_admin_responses_colrestitle, 'sort'=>1, 'fields'=>'pathname',
                        'code'=>'<a href="'.$basepgurl.'&resourceid=%s&showdeleted='.$showdeleted.'">%s</a>',
                        'linkfields'=>['xResponse', 'pathname'], ];
    //$ar[] = array('type'=>'string','label'=>lg_admin_responses_folder,'sort'=>1,'width'=>'300','fields'=>'sFolder');
    $ar[] = ['type'=>'string', 'label'=>lg_admin_responses_perms, 'sort'=>1, 'width'=>'100', 'fields'=>'fType', 'function'=>'responsePermLabel'];
    $ar[] = ['type'=>'string', 'label'=>lg_admin_responses_recurring, 'sort'=>1, 'width'=>'100', 'fields'=>'fRecurringRequest', 'function'=>'responseRecurringLabel'];

    if (isAdmin()) {
        $ar[] = ['type'=>'string', 'label'=>lg_admin_responses_createdby, 'sort'=>1, 'width'=>'100', 'fields'=>'fullname'];
    }

    if( $showdeleted == 0 ) {
        $ar[] = ['type'=>'link','label'=>'','sort'=>0,'nowrap'=>true,'width'=>30,
            'code'=>'<a style="padding: 0 6px;" href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.responses', 'action'=>'delete', 'resourceid'=>'%s'])).'" onClick="return hs_confirm(\''.lg_admin_responses_resdelwarn.'\',\''.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.responses', 'action'=>'delete', 'resourceid'=>'%s'])).'\');">
            <img src="' . static_url() . '/static/img5/trash-solid.svg" style="width: 14px;" />
        </a>',
            'fields'=>'xResponse', 'linkfields'=>['xResponse','xResponse']];
    } else {
        $ar[] = ['type'=>'link','label'=>'','sort'=>0,'nowrap'=>true,'width'=>30,
            'code'=>'<a style="padding: 0 6px;" href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.responses', 'action'=>'undelete', 'resourceid'=>'%s'])).'" onClick="return hs_confirm(\''.lg_admin_responses_restorewarn.'\',\''.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.responses', 'action'=>'undelete', 'resourceid'=>'%s'])).'\');">
            <img src="' . static_url() . '/static/img5/trash-undo-alt-solid.svg" style="width: 14px;" />
        </a>',
            'fields'=>'xResponse', 'linkfields'=>['xResponse','xResponse']];
    }

    $addBtn = '';
    if (! $resourceid and $data->RecordCount() > 5) {
        $addBtn .= '<div class="buttonbar" style="margin-left: 10px;"><a href="#responsesformsection" class="btn btn-inline tiny">'.$title.'</a></div>';
    }

    $datatable = recordSetTable($data, $ar,
                                //options
                                ['sortby'=>$sortby,
                                      'sortord'=>$sortord,
                                      'title'=>lg_admin_responses_title.$addBtn.$dellable,
                                      'paginate'=>false, //($showdeleted ? false : $paginate),
//									  'paginate_ct'=>20,
//									  'paginate_sim'=>true,
                                      'noresults' => lg_noresults,
                                      'showcount'=>$data->RecordCount(), //needed for pagination
                                      'rowsonly'=>($_GET['rowsonly'] ? true : false),
                                      'title_right'=>$showdellink.displaySmallSearchBox('responses', lg_admin_responses_search, ['showdeleted'=>$showdeleted]), ], $basepgurl);

    //This is an ajax request, return results right here and exit
    if (isset($_GET['ajax'])) {
        //Set a header with the correct charset
        header('Content-Type: text/html; charset=UTF-8');
        $htmldirect = true;
        $out = [];
        $out['html'] = $datatable;
        echo json_encode($out);
        exit;
    }
}

// If looking at a specific category show delete/restore option
if (! empty($resourceid) && $showdeleted == 0) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_responses_resdelwarn.'\',\''.$basepgurl.'&action=delete&resourceid='.$resourceid.'\');">'.lg_admin_responses_resdel.'</button>';
}
if (! empty($resourceid) && $showdeleted == 1) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($fm['sResponseTitle']).'\',\''.$basepgurl.'&action=undelete&resourceid='.$resourceid.'\');">'.lg_restore.'</button>';
}

$statusSelect = '<option value=""></option>';
$activeStatus = apiGetActiveStatus();
foreach ($activeStatus as $key=>$value) {
    if ($key != 2) {
        $statusSelect .= '<option value="'.$key.'" '.selectionCheck($key, $fm['xStatus']).'>'.$value.'</option>';
    }
}

$catsList = apiGetAllCategories(0, '');
$catsSelect = categorySelectOptions($catsList, $fm['xCategory']);

//Folder list
$fs = apiGetRequestResponseFolders($user);
$folderSel = '<option value="'.lg_admin_responses_myfolder.'">'.lg_admin_responses_myfolder.'</option>';
if (is_array($fs) && ! empty($fs)) {
    foreach ($fs as $k=>$v) {
        $folderSel .= '<option value="'.$v['sFolder'].'" '.selectionCheck($v['sFolder'], hs_htmlspecialchars($fm['sFolder'])).'>'.$v['sFolder'].'</option>';
    }
}

//active users
$activeUsers = apiGetAllUsers();
$ownerSel = '';
$activeUsersSel = '<option value=""></value>';
$activeUsersSel .= '<option value="0" '.(intval($fm['xPersonAssignedTo']) === 0 && $fm['xPersonAssignedTo'] != '' ? 'selected' : '').'>'.lg_inbox.'</value>';
if (hs_rscheck($activeUsers)) {
    while ($u = $activeUsers->FetchRow()) {
        $ownerSel .= '<option value="'.$u['xPerson'].'" '.selectionCheck($u['xPerson'], $fm['xPerson']).'>'.$u['fullname'].'<br>';
        $activeUsersSel .= '<option value="'.$u['xPerson'].'" '.selectionCheck($u['xPerson'], $fm['xPersonAssignedTo']).'>'.$u['fullname'].'<br>';
    }
}

$mailboxesSelect = '<select name="emailfrom" id="emailfrom" class="hdform">';
$mailboxesSelect .= '<option value=""></option>';
$mailboxesSelect .= '<option value="'.hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILNAME')).'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0" '.selectionCheck(hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILNAME')).'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0', $fm['emailfrom']).'>'.hs_jshtmlentities(lg_default_mailbox).' - '.hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILACCT')).'</option>';
$mailboxesres = apiGetAllMailboxes(0, '');
if (is_object($mailboxesres) && $mailboxesres->RecordCount() > 0) {
    while ($box = $mailboxesres->FetchRow()) {
        if (! hs_empty($box['sReplyEmail'])) {
            $mailboxesSelect .= '<option value="'.hs_jshtmlentities($box['sReplyName']).'*'.hs_jshtmlentities($box['sReplyEmail']).'*'.$box['xMailbox'].'" '.selectionCheck(hs_jshtmlentities($box['sReplyName']).'*'.hs_jshtmlentities($box['sReplyEmail']).'*'.$box['xMailbox'], $fm['emailfrom']).'>'.hs_jshtmlentities(replyNameDisplay($box['sReplyName'])).' - '.hs_jshtmlentities($box['sReplyEmail']).'</option>';
        }
    }
}
$mailboxesSelect .= '<option value="dontemail" '.selectionCheck('dontemail', $fm['emailfrom']).'>'.hs_jshtmlentities(lg_admin_responses_dontemail).'</option>';
$mailboxesSelect .= '</select>'.errorMessage('emailfrom');

/*****************************************
JAVASCRIPT
*****************************************/
$fm['xReportingTags'] = is_array($fm['xReportingTags'])
    ? $fm['xReportingTags']
    : [];

$headscript = '
<script type="text/javascript">
//Set response box editor type
editor_type = "'.$editor_type.'";

Event.observe(window, \'load\', resize_all_textareas, false);
Event.observe(window, \'load\', showRepTags, false);

	function getResponse(responseid){
		goPage("'.route('admin', ['pg' => 'admin.responses']).'&resourceid="+responseid);
	}

	function remove_folder_prompt(){
		Element.remove("add_folder_prompt");
	}

	function showRepTags(){
		var checkedtags = Array("'.implode('","', $fm['xReportingTags']).'");

		if($F("xCategory")){
			var call = new Ajax.Request(
				"'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'rep_tags_for_cat']).'",
				{
					method: 	"get",
					parameters: {xCategory:$F("xCategory"),rand:ajaxRandomString()},
					onSuccess:  function(transport){
									var tags = eval("("+transport.responseText+")");

									$("xReportingTags").update();

									for(i=0;i < tags.length;i++){
										var checked = (checkedtags.indexOf(String(tags[i][0])) >= 0 ? "checked" : "");
										$("xReportingTags").insert(\'<div style="display:flex;align-items:center;margin:8px 0;"><input type="checkbox" name="xReportingTags[]" value="\'+tags[i][0]+\'" \'+checked+\' style="margin-right:6px;" /> \' + tags[i][1] + "</div>");
									}

									if(tags.length == 0) $("xReportingTags").insert("-");
								}
				});
		}
	}

	Event.observe(window,"load",function(){
		new Control.Tabs("actiontabs");
	});

	$jq().ready(function(){
		function rebind() {
			$jq(".js-attach-remove").on("click", function(e){
				e.preventDefault();
				$jq(this).parent().remove();
			});
		}
		rebind();
		$jq(".js-add-attachment").on("click", function(e){
			e.preventDefault();
			var attachEl = $jq("#js-attach").html();
			$jq(".js-attach-holder").append(attachEl);
			rebind();
		});
	});
</script>';

if (hs_setting('cHD_HTMLEMAILS')) {
    //Setup markdown editing
    $headscript .= markdown_setup('tResponse');
}

$onload = 'setFieldFocus(document.getElementById(\'sResponseTitle\'));';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

$attachments = '';
if (! empty($fm['attachment'])) {
    foreach ($fm['attachment'] as $attachment) {
        $file = $GLOBALS['DB']->GetRow('SELECT sFilename FROM HS_Documents WHERE xDocumentId = ?', [$attachment]);
        $attachments .= '<div class="js-attach-div" style="padding: 0 0 10px 0;">'.$file['sFilename'].'
			<input type="hidden" name="doc[]" value="'.$attachment.'">
			<a href="#" class="js-attach-remove"><img src="'.static_url().'/static/img5/remove.svg" alt="" title="'.lg_admin_responses_addfolder.'" style="margin-top:2px;" border="0" align="top"></a>
		</div>';
    }
}

$pagebody .= '
<form action="'.$basepgurl.'&action='.$formaction.'&resourceid='.$resourceid.'" method="POST" name="responsesform" id="responsesform" enctype="multipart/form-data" accept-charset="UTF-8">
'.csrf_field().'
'.$feedbackArea.'
 '.$datatable.'

    '.renderInnerPageheader($title, lg_admin_responses_explanation).'

    <div class="card padded" id="responsesformsection">

        <div class="fr">
            <div class="label">
                <label class="req" for="sResponseTitle">'.lg_admin_responses_restitle.'</label>
            </div>
            <div class="control">
                <input tabindex="100" name="sResponseTitle" id="sResponseTitle" type="text" size="40" value="'.formCleanHtml($fm['sResponseTitle']).'" class="'.errorClass('sResponseTitle').'"> '.errorMessage('sResponseTitle').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="" for="tResponse">'.lg_admin_responses_response.'</label>
            </div>
            <div class="control">
                <!-- div needed for autoexpanding text area -->
                <div class="group vertical">
                    <div id="wysiwyg_wrap_div" style="width:98%;">
                        <span id="spell_container"></span>
                        <textarea tabindex="101" name="tResponse" id="tResponse" cols="70" rows="20" style="width:100%;height:150px;" class="'.errorClass('tResponse').'">'.formCleanHtml($fm['tResponse']).'</textarea>
                    </div>
                    '.tagDrop('tResponse').'
                    '.errorMessage('tResponse').'
                </div>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="" for="sFolder">
                    '.lg_admin_responses_folder.'
                </label>
                <div class="info">'.lg_admin_responses_foldernameex.'</div>
            </div>
            <div class="control">
                <div style="display:flex;align-items:center;">
                    <select name="sFolder" id="sFolder" style="flex:1;">
                    '.$folderSel.'
                    </select>
                    <a href="javascript:addFolder(\'\',\'sFolder\');" class="btn inline-action" style="margin-left:10px;">'.lg_admin_responses_addfolder.'</a>
                </div>
                '.errorMessage('sFolder').'
            </div>
        </div>

        <div class="hr"></div>

        '.(isAdmin() ? '
            <div class="fr">
                <div class="label">
                    <label class="" for="sFolder">'.lg_admin_responses_owner.'</label>
                </div>
                <div class="control">
                    <select name="xPerson">'.$ownerSel.'</select>
                </div>
            </div>
            <div class="hr"></div>' : '').'

        <div class="fr">
            <div class="label">
                <label class="" for="">
                    '.lg_lookup_filter_attachment2.'
                </label>
            </div>
            <div class="control">
                <div class="group vertical">
                    <div id="js-attach" style="display:none;">
                        <div class="js-attach-div" style="padding: 0 0 10px 0;">
                            <input type="file" size="60" name="doc[]">
                            <!--<a href="#" class="js-attach-remove"><img src="'.static_url().'/static/img5/remove.svg" alt="" style="margin-top:2px;height:24px;" border="0" align="top"></a>-->
                        </div>
                    </div>
                    <div class="js-attach-holder">
                        '.$attachments.'
                    </div>
                    <a href="#" class="js-add-attachment btn inline-action" style="width:50%;">'.lg_admin_responses_addattachment.'</a>
                </div>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="" for="">'.lg_admin_responses_perms.'</label>
            </div>
            <div class="control">
                '.permSelectUI($fm['fType'], $fm['fPermissionGroup'], $GLOBALS['DB']->GetCol('SELECT xPerson FROM HS_Response_People WHERE xResponse = ?', [$resourceid]), $GLOBALS['DB']->GetCol('SELECT xGroup FROM HS_Response_Group WHERE xResponse = ?', [$resourceid])).'
            </div>
        </div>';

        $pagebody .= displayContentBoxTop(lg_admin_responses_advoptions).'
    	<div class="tab_wrap">

    		<ul class="tabs" id="actiontabs">
    			<li><a href="#details_tab" class="active"><span>'.lg_admin_responses_togglerd.'</span></a></li>
    			'.($GLOBALS['customFields'] ? '<li><a href="#cf_tab"><span>'.lg_admin_responses_togglecf.'</span></a></li>' : '').'
    			<li><a href="#options_tab"><span>'.lg_admin_responses_togglenote.'</span></a></li>
    		</ul>

    		<div id="details_tab" name="details_tab">

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="xStatus">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_status.'</label>
                    </div>
                    <div class="control">
                        <select name="xStatus">'.$statusSelect.'</select>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="xCategory">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_category.'</label>
                    </div>
                    <div class="control">
                        <select name="xCategory" id="xCategory" onchange="showRepTags();"  class="'.errorClass('xCategory').'">'.$catsSelect.'</select>
                        '.errorMessage('xCategory').'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="xReportingTags">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_reptags.'</label>
                    </div>
                    <div class="control">
                        <div id="xReportingTags">-</div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="xPersonAssignedTo">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_assigned.'</label>
                        <div class="info">'.lg_admin_responses_adv_assignednote.'</div>
                    </div>
                    <div class="control">
                        <select name="xPersonAssignedTo">'.$activeUsersSel.'</select>
                    </div>
                </div>

    		</div>

    		'.($GLOBALS['customFields'] ? '
    		<div id="cf_tab" name="cf_tab" style="display:none;">
    			'.renderCustomFields($fm, $GLOBALS['customFields'], '200', true, false, '', false, false, true).'
    		</div>' : '').'

    		<div id="options_tab" name="options_tab" style="display:none;">

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="sTitle">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_subject.'</label>
                    </div>
                    <div class="control">
                        <input name="sTitle" id="sTitle" type="text" size="40" value="'.formClean($fm['sTitle']).'">
                    </div>
                </div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="fPublic">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_note.'</label>
                    </div>
                    <div class="control">
                        <select name="fPublic">
                            <option value=""></option>
                            <option value="1" '.selectionCheck('1', $fm['fPublic']).'>'.lg_admin_responses_adv_pub.'</option>
                            <option value="2" '.selectionCheck('2', $fm['fPublic']).'>'.lg_admin_responses_adv_priv.'</option>
                            <option value="3" '.selectionCheck('3', $fm['fPublic']).'>'.lg_admin_responses_adv_ext.'</option>
                        </select>
                    </div>
                </div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="emailfrom">'.lg_admin_responses_adv_change.' '.lg_admin_responses_adv_emailfrom.'</label>
                    </div>
                    <div class="control">
                        '.$mailboxesSelect.'
                    </div>
                </div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="togroup">'.lg_admin_responses_adv_add.' '.lg_admin_responses_adv_tofield.'</label>
                        <div class="info">'.lg_admin_responses_adv_tofield_note.' '.lg_admin_responses_adv_sepcomma.'</div>
                    </div>
                    <div class="control">
                        <input name="togroup" id="togroup" type="text" size="60" style="width:95%" value="'.formClean($fm['togroup']).'">
                    </div>
                </div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="ccgroup">'.lg_admin_responses_adv_add.' '.lg_admin_responses_adv_cc.'</label>
                        <div class="info">'.lg_admin_responses_adv_sepcomma.'</div>
                    </div>
                    <div class="control">
                        <input name="ccgroup" id="ccgroup" type="text" size="60" style="width:95%" value="'.formClean($fm['ccgroup']).'">
                    </div>
                </div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="bccgroup">'.lg_admin_responses_adv_add.' '.lg_admin_responses_adv_bcc.'</label>
                        <div class="info">'.lg_admin_responses_adv_sepcomma. '</div>
                    </div>
                    <div class="control">
                        <input name="bccgroup" id="bccgroup" type="text" size="60" style="width:95%" value="'.formClean($fm['bccgroup']).'">
                    </div>
                </div>
    		</div>

    	</div>
        '.displayContentBoxBottom();

        $pagebody .= displayContentBoxTop(lg_admin_responses_scheduling, lg_admin_responses_scheduling_info).'

                <div class="fr">
                    <div class="label tdlcheckbox">
                        <label for="fRecurringRequest" class="datalabel">'. lg_admin_responses_scheduling_enabled .':</label>
                    </div>
                    <div class="control">
                        <input type="checkbox" name="fRecurringRequest" id="fRecurringRequest" class="checkbox" value="1" ' . checkboxCheck(1, $fm['fRecurringRequest']) . '>
                        <label for="fRecurringRequest" class="switch"></label>
                    </div>
                </div>

                <div class="recurring">
                    <div class="hr"></div>

                    <fieldset class="fieldset">
                        <div class="sectionhead">'. lg_admin_responses_scheduling_customerinfo .'</div>

                        <div class="fr">
                            <div class="label"><label for="sFirstName" class="datalabel">' . lg_admin_responses_first_name. '</label></div>
                            <div class="control">
                                <input name="sFirstName" id="sFirstName" type="text" size="25" value="' . formClean($fm['sFirstName']) . '" class="' . errorClass('sFirstName') . '">
                                ' . errorMessage('sFirstName') . '
                            </div>
                        </div>

                        <div class="hr"></div>

                        <div class="fr">
                            <div class="label"><label for="sLastName" class="datalabel">' . lg_admin_responses_last_name. '</label></div>
                            <div class="control">
                                <input name="sLastName" id="sLastName" type="text" size="25" value="' . formClean($fm['sLastName']) . '" class="' . errorClass('sLastName') . '">
                                ' . errorMessage('sLastName') . '
                            </div>
                        </div>

                        <div class="hr"></div>

                        <div class="fr">
                            <div class="label"><label for="sEmail" class="datalabel">' . lg_admin_responses_email. '</label></div>
                            <div class="control">
                                <input name="sEmail" id="sEmail" type="text" size="25" value="' . formClean($fm['sEmail']) . '" class="' . errorClass('sEmail') . '">
                                ' . errorMessage('sEmail') . '
                            </div>
                        </div>

                        <div class="hr"></div>

                        <div class="fr">
                            <div class="label"><label for="sPhone" class="datalabel">' . lg_admin_responses_phone. '</label></div>
                            <div class="control">
                                <input name="sPhone" id="sPhone" type="text" size="25" value="' . formClean($fm['sPhone']) . '" class="' . errorClass('sPhone') . '">
                                ' . errorMessage('sPhone') . '
                            </div>
                        </div>

                        <div class="hr"></div>

                        <div class="fr">
                            <div class="label"><label for="sUserId" class="datalabel">' . lg_admin_responses_customer_id. '</label></div>
                            <div class="control">
                                <input name="sUserId" id="sUserId" type="text" size="25" value="' . formClean($fm['sUserId']) . '" class="' . errorClass('sUserId') . '">
                                ' . errorMessage('sUserId') . '
                            </div>
                        </div>

                    </fieldset>

                                    <div class="sectionhead">'.lg_admin_responses_create_schedule.'</div>
                                    <select name="fSendEvery" id="fSendEvery" style="width: 180px;">
                                        <option value="daily" '.(($fm['fSendEvery'] == "daily") ? "selected=selected" : "") .'>Every Day</option>
                                        <option value="weekly" '.(($fm['fSendEvery'] == "weekly") ? "selected=selected" : "") .'>Every Week</option>
                                        <option value="monthly" '.(($fm['fSendEvery'] == "monthly") ? "selected=selected" : "") .'>Monthly</option>
                                    </select>
                                    <span id="send_on_days">
                                        <label class="datalabel req" for="fSendDay" style="display: inline">on</label>
                                        <select name="fSendDay" id="fSendDay">
                                            <option value="Monday" '.(($fm['fSendDay'] == "Monday") ? "selected=selected" : "") .'>Monday</option>
                                            <option value="Tuesday" '.(($fm['fSendDay'] == "Tuesday") ? "selected=selected" : "") .'>Tuesday</option>
                                            <option value="Wednesday" '.(($fm['fSendDay'] == "Wednesday") ? "selected=selected" : "") .'>Wednesday</option>
                                            <option value="Thursday" '.(($fm['fSendDay'] == "Thursday") ? "selected=selected" : "") .'>Thursday</option>
                                            <option value="Friday" '.(($fm['fSendDay'] == "Friday") ? "selected=selected" : "") .'>Friday</option>
                                            <option value="Saturday" '.(($fm['fSendDay'] == "Saturday") ? "selected=selected" : "") .'>Saturday</option>
                                            <option value="Sunday" '.(($fm['fSendDay'] == "Sunday") ? "selected=selected" : "") .'>Sunday</option>
                                        </select>
                                    </span>
                                    <label class="datalabel req monthly_label" for="fSendTime" style="display: none">on the last day at</label>
                                    <label class="datalabel req at_label" for="fSendTime" style="display: inline">at</label>
                                    <select name="fSendTime" id="fSendTime">
                                        '.hs_ShowBizHours(($fm['fSendTime']) ? $fm['fSendTime'] : 8).'
                                    </select>

                </div>

        '.displayContentBoxBottom().'
    </div>';

    $pagebody .= '
    <div class="button-bar space">
        <div class="">
            <button type="submit" name="submit" id="submit" class="btn accent">'.$button.'</button>
            '.$secondary_button.'
        </div>
        '.$delbutton. '
    </div>

</form>
<script>
    $jq(document).ready(function(){

        showHideRecurring();
        $jq("#fRecurringRequest").on("click", function(e){
            showHideRecurring();
    	});

        function showHideRecurring() {
           if($jq("#fRecurringRequest").is(":checked")) {
    			$jq(".recurring").show();
    		} else {
                $jq(".recurring").hide();
            }
        }
        function showDaily() {
            $jq("#send_on_days").hide();
            $jq(".monthly_label").hide();
            $jq(".at_label").show();
        }
        function showWeekly() {
            $jq("#send_on_days").show();
            $jq(".monthly_label").hide();
            $jq(".at_label").show();
        }
        function showMonthly() {
            $jq("#send_on_days").hide();
            $jq(".at_label").hide();
            $jq(".monthly_label").show().css("display", "inline");
        }
        $jq("#send_on_days").hide();
        $jq("#send_on_date").hide();
        '. (($fm['fSendEvery'] == "monthly") ? "showMonthly();" : "") .'
        '. (($fm['fSendEvery'] == "weekly") ? "showWeekly();" : "") .'
        '. (($fm['fSendEvery'] == "daily") ? "showDaily();" : "") .'
        $jq("#fSendEvery").on("change", function(){
            if ($jq(this).val() == "daily") {
                showDaily();
            } else if ($jq(this).val() == "monthly") {
                showMonthly();
            } else {
                showWeekly();
            }
        });
    });
    </script>
';
