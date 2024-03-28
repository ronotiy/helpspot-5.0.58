<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.users.lib.php';
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.requestupdate.php';

/*****************************************
PREF SETTINGS
- when being accessed as a pref page
*****************************************/
$prefParams = [];
$pref = '';
if (isset($_GET['pref'])) {
    $_GET['resourceid'] = $user['xPerson'];
    $_POST['fUserType'] = $user['fUserType'];
    $fm['updatecats'] = false; //don't update cats if this form is being used by normal user
    $prefParams['pref'] = '1';
    $pref = '&pref=1';
} elseif (! isAdmin()) {
    die();
}

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', array_merge($prefParams, ['pg' => 'admin.users']));
$pagetitle = lg_admin_users_title;
$tab = isAdmin() ? 'nav_admin' : 'nav_workspace';
$subtab = 'nav_admin_staff';
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortord = isset($_GET['sortord']) ? $_GET['sortord'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$resourceid = isset($_GET['resourceid']) && is_numeric($_GET['resourceid']) ? $_GET['resourceid'] : 0;
$showdeleted = isset($_GET['showdeleted']) ? $_GET['showdeleted'] : 0;
$paginate = isset($_GET['paginate']) && is_numeric($_GET['paginate']) ? $_GET['paginate'] : 0;

$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

$feedbackArea = '';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$datatable = '';
$delbutton = '';
$photo = isset($_FILES['photoupload']['tmp_name']) ? $_FILES['photoupload']['tmp_name'] : '';
$overlimit = false;

$fm['sFname'] = isset($_POST['sFname']) ? $_POST['sFname'] : '';
$fm['sLname'] = isset($_POST['sLname']) ? $_POST['sLname'] : '';
$fm['sUsername'] = isset($_POST['sUsername']) ? $_POST['sUsername'] : '';
$fm['sEmail'] = isset($_POST['sEmail']) ? $_POST['sEmail'] : '';
$fm['sSMS'] = isset($_POST['sSMS']) ? $_POST['sSMS'] : '';
$fm['sPassword'] = isset($_POST['sPassword']) ? $_POST['sPassword'] : '';
$fm['sEmail2'] = isset($_POST['sEmail2']) ? $_POST['sEmail2'] : '';
$fm['sPhone'] = isset($_POST['sPhone']) ? $_POST['sPhone'] : '';
$fm['tSignature'] = isset($_POST['tSignature']) ? $_POST['tSignature'] : '';
$fm['tSignature_HTML'] = isset($_POST['tSignature_HTML']) ? $_POST['tSignature_HTML'] : '';
$fm['xSMSService'] = isset($_POST['xSMSService']) ? $_POST['xSMSService'] : 0;
$fm['emailnewuser'] = isset($_POST['emailnewuser']) ? true : false;
$fm['xPersonPhotoId'] = isset($_POST['xPersonPhotoId']) ? $_POST['xPersonPhotoId'] : 0;
$fm['sEmoji'] = isset($_POST['sEmoji']) ? $_POST['sEmoji'] : '';
$fm['fUserType'] = isset($_POST['fUserType']) ? $_POST['fUserType'] : 2;
$fm['fDarkMode'] = isset($_POST['fDarkMode']) ? $_POST['fDarkMode'] : 0;
$fm['fNotifyEmail'] = isset($_POST['fNotifyEmail']) ? $_POST['fNotifyEmail'] : 0;
$fm['fNotifyEmail2'] = isset($_POST['fNotifyEmail2']) ? $_POST['fNotifyEmail2'] : 0;
$fm['fNotifySMS'] = isset($_POST['fNotifySMS']) ? $_POST['fNotifySMS'] : 0;
$fm['fDefaultToPublic'] = isset($_POST['fDefaultToPublic']) ? $_POST['fDefaultToPublic'] : 0;
$fm['fKeyboardShortcuts'] = isset($_POST['fKeyboardShortcuts']) ? $_POST['fKeyboardShortcuts'] : 0;
$fm['fHideWysiwyg'] = isset($_POST['fHideWysiwyg']) ? $_POST['fHideWysiwyg'] : 0;
$fm['fHideImages'] = isset($_POST['fHideImages']) ? $_POST['fHideImages'] : 0;
$fm['fReturnToReq'] = isset($_POST['fReturnToReq']) ? $_POST['fReturnToReq'] : 0;
$fm['iRequestHistoryLimit'] = isset($_POST['iRequestHistoryLimit']) ? $_POST['iRequestHistoryLimit'] : 10;
$fm['fRequestHistoryView'] = isset($_POST['fRequestHistoryView']) ? $_POST['fRequestHistoryView'] : 1;
$fm['sHTMLEditor'] = isset($_POST['sHTMLEditor']) ? $_POST['sHTMLEditor'] : '';
$fm['xCatList'] = isset($_POST['xCatList']) ? $_POST['xCatList'] : [];
$fm['fNotifySMSUrgent'] = isset($_POST['fNotifySMSUrgent']) ? $_POST['fNotifySMSUrgent'] : 0;
$fm['fNotifyNewRequest'] = isset($_POST['fNotifyNewRequest']) ? $_POST['fNotifyNewRequest'] : 0;
$fm['xPersonOutOfOffice'] = isset($_POST['xPersonOutOfOffice']) ? $_POST['xPersonOutOfOffice'] : 0;
$showNotifyNewRequest = true;

//All active users
$userList = apiGetAllUsers();

if (! subscription()->canAdd('user', $userList->RecordCount())) {
    $overLimit = true;
    $text = '<div style="display:flex;justify-content: space-between;margin: 20px 0;" id="notification-'.$notification->id.'">
                <div>
                    You have reached the free plan user limit. If you need more users please move to a paid account
                    <a class="action" href="https://store.helpspot.com">buy now</a>
                    or <a class="action" href="https://www.helpspot.com/talk-to-sales">contact sales</a>
                </div>
            </div>';
    $pagebody .= displaySystemBox($text);
}

/* Anytime this page is visited clear the cache.
   Allows any changes to clear the cache and also acts as an emergency cache clear */
\Facades\HS\Cache\Manager::forgetGroup('users')
    ->forget([
        \Facades\HS\Cache\Manager::key('CACHE_ASSIGNEDSTAFF_KEY'),
    ]);

/*****************************************
PERFORM ACTIONS
*****************************************/
//Protect from nonadmins trying to hack
if (! isAdmin() && ($action == 'add' || $action == 'delete' || $action == 'undelete')) {
    die();
}

if ($action == 'edit' && ! isAdmin() && $resourceid != $user['xPerson']) {
    die();
} //only allow edits to own acct

if ($action == 'add' || $action == 'edit') {

    // add these two items to fm array then pass entire thing in to be processed
    $fm['resourceid'] = $resourceid;
    $fm['mode'] = $action;

    //Add in columns that are not modified here, but are stored with the person info
    $orig_user_details = apiGetUser($resourceid);
    $fm['tWorkspace'] = $orig_user_details['tWorkspace'];
    $fm['sWorkspaceDefault'] = $orig_user_details['sWorkspaceDefault'];

    //Upload new photo if sent
    if (! empty($photo) && in_array($_FILES['photoupload']['type'], $GLOBALS['imageMimeTypes'])) {
        if ($_FILES['photoupload']['size'] < 150000) {
            $upload['blobPhoto'] = file_get_contents($photo);
            $upload['sDescription'] = '';
            $upload['sFilename'] = $_FILES['photoupload']['name'];
            $upload['sFileMimeType'] = $_FILES['photoupload']['type'];
            $upload['xPerson'] = $user['xPerson'];

            $photoID = apiUserImageUpload($upload);
            if ($photoID) {
                $fm['xPersonPhotoId_reset'] = true;
                $fm['xPersonPhotoId'] = $photoID;
            }
        }
    }

    $Res = apiAddEditUser($fm, __FILE__, __LINE__);

    // if it's an array of errors than skip else continue
    if (! is_array($Res)) {
        $feedback = $resourceid != '' ? ($pref ? lg_admin_users_fbupdated : lg_admin_users_fbedited) : lg_admin_users_fbadded;
        return redirect()
            ->route('admin', array_merge($prefParams, ['pg' => 'admin.users']))
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
    $feedback = $action == 'delete' ? lg_admin_users_fbdeleted : lg_admin_users_fbundeleted;
    $delCat = apiDeleteResource('HS_Person', 'xPerson', $resourceid, $action);

    if ($action == 'delete') {
        // Remove them from any assigned categories.
        removePersonFromAllCats($resourceid);

        //set any requests assigned to this user back to inbox
        $ft = new hs_filter();
        $ft->useSystemFilter('myq');
        $ft->filterDef['xPersonAssignedTo'] = $resourceid;	//get users requests
        $ftrs = $ft->outputResultSet();

        if (hs_rscheck($ftrs)) {
            while ($row = $ftrs->FetchRow()) {
                $fm = apiGetRequest($row['xRequest']);
                $fm['dtGMTOpened'] = date('U');	//current dt
                $fm['xPersonAssignedTo'] = 0;	//assign back to inbox
                $fm['override_autoassign'] = true; //override auto assign so always go to inbox

                $update = new requestUpdate($row['xRequest'], $fm, $user['xPerson'], __FILE__, __LINE__);
                $reqResult = $update->checkChanges();
            }
        }
    }

    // Redirect Back
    return redirect()
        ->route('admin', array_merge($prefParams, ['pg' => 'admin.users']))
        ->with('feedback', $feedback);
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/

if (! empty($resourceid)) {

    //Get resource info if there are no form errors. If there was an error then we don't want to get data again
    // that would overwrite any changes the user made
    if (empty($formerrors)) {
        $fm = apiGetUser($resourceid);

        if(!empty($fm['sEmoji'])){
            $emoji = new \JoyPixels\Client(new \JoyPixels\Ruleset());

            $fm['sEmoji'] = $emoji->shortnameToUnicode($fm['sEmoji']);
        }
    }

    // If they don't have inbox access or if they only see their own
    // requests then make sure they can't receive new req notifications
    $perm = apiPermGetById($fm['fUserType']);
    if ($perm['fViewInbox'] == 0 || $perm['fCanViewOwnReqsOnly'] == 1) {
        $fm['fNotifyNewRequest'] = 0;
        $showNotifyNewRequest = false;
    }

    $formaction = 'edit';
    if (! isset($_GET['pref'])) {
        $title = lg_admin_users_editcat.$fm['sFname'].' '.$fm['sLname'];
    } else {
        $title = lg_admin_users_mgprefs;
    }
    $button = lg_admin_users_editbutton;
    $showdellink = '';
    $showform = true;
} elseif ($action == '' || ! empty($formerrors)) {
    //Set to allow notifications by default
    $fm['fNotifyEmail'] = 1;

    // Get category info
    $data = apiGetAllUsers($showdeleted, ($sortby . ' ' . $sortord));
    $formaction = 'add';
    $title = lg_admin_users_addcat;
    $button = lg_admin_users_addbutton;
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_users_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_users_noshowdel.'</a>';
    }
    // build data table
    $dataarray = [];
    $datatable = recordSetTable($data,
                                 [
                                    ['type'=>'string', 'label'=>lg_admin_users_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xPerson'],
                                    ['type'=>'link', 'label'=>lg_admin_users_coluser, 'sort'=>1, 'fields'=>'fullname',
                                            'code'=>'<a href="'.$basepgurl.'&resourceid=%s&showdeleted='.$showdeleted.'">%s</a>',
                                            'linkfields'=>['xPerson', 'fullname'], ],
                                    ['type'=>'string', 'label'=>lg_admin_users_colut, 'sort'=>0, 'width'=>'200', 'fields'=>'sGroup'],
                                    ['type'=>'link', 'label'=>lg_admin_users_colemail, 'sort'=>1, 'width'=>'140', 'fields'=>'sEmail',
                                            'code'=>'<a href="mailto:%s">%s</a>',
                                            'linkfields'=>['sEmail', 'sEmail'], ],
                                    ],
                                    ['sortby'=>$sortby,
                                           'sortord'=>$sortord,
                                           'title'=>$pagetitle.$dellable,
                                           'paginate'=>$paginate,
                                           'paginate_ct'=>20,
                                           'paginate_sim'=>true,
                                           'rowsonly'=>(isset($_GET['rowsonly']) ? true : false),
                                           'showcount'=>$data->RecordCount(),
                                             'showdeleted'=>$showdeleted,
                                           'title_right'=>$showdellink.displaySmallSearchBox('staff', lg_admin_users_search, ['showdeleted'=>$showdeleted]), ], $basepgurl);

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

    $showform = ! licenseAtUserLimit();
}

//If looking at a specific user show delete/restore option
if (! empty($resourceid) && $showdeleted == 0 && isAdmin()) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="if(notCatDefault()){hs_confirm(\''.lg_admin_users_coldelwarn.'\',\''.$basepgurl.'&action=delete&resourceid='.$resourceid.'\');}">'.lg_admin_users_coldel.'</button>';
}

if (! empty($resourceid) && $showdeleted == 1 && isAdmin() && ! licenseAtUserLimit()) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($fm['sFname'].' '.$fm['sLname']).'\',\''.$basepgurl.'&action=undelete&resourceid='.$resourceid.'\');">'.lg_restore.'</button>';
}

//Don't show button if admin in prefs mode
if (! empty($pref)) {
    $delbutton = '';
}

// Dynamnic parts for pagebody form
$catsList = apiGetAllCategories(0, '');
$catsSelect = '';
$catsArray = [];
if (hs_rscheck($catsList)) {
    $catsSelect .= '<a class="btn inline-action js-check-all" href="#">'.lg_checkbox_checkall.'</a>';
    while ($c = $catsList->FetchRow()) {
        if ($c['fDeleted'] == 0) {
            $group = (empty($c['sCategoryGroup']) ? '' : $c['sCategoryGroup'].' / ');
            $userar = hs_unserialize($c['sPersonList']);
            $isdefault = ($c['xPersonDefault'] == $resourceid && $resourceid != 0) ? '('.lg_admin_users_default.')' : '';
            $catsSelect .= '<div class="categoryListItem"><input type="checkbox" name="xCatList[]" id="cat_'.$c['xCategory'].'" class="canCheck" value="'.$c['xCategory'].'" style="vertical-align:middle;" ';
            //if it's new staffer check all by default
            if ($resourceid == 0) {
                $catsSelect .= 'checked';
            } else {
                $catsSelect .= checkboxMuiltiboxCheck($resourceid, $userar);
            }
            $catsSelect .= ' /><label for="cat_'.$c['xCategory'].'" style="cursor: pointer;">'.$group.$c['sCategory'].' '.$isdefault.'</label></div>';
        }
    }
}

$smsSelect = '';
$smssytems = apiGetSMSSystems();
while ($s = $smssytems->FetchRow()) {
    $smsSelect .= '<option value="'.$s['xSMSService'].'" '.selectionCheck($s['xSMSService'], $fm['xSMSService']).'>'.$s['sName'].'</option>';
}

$groupSelect = '';
$grouprs = $GLOBALS['DB']->Execute('SELECT xGroup,sGroup FROM HS_Permission_Groups ORDER BY xGroup');
while ($s = $grouprs->FetchRow()) {
    $groupSelect .= '<option value="'.$s['xGroup'].'" '.selectionCheck($s['xGroup'], $fm['fUserType']).'>'.$s['sGroup'].'</option>';
}

// out of office user list
$usersSelect = '';
while ($u = $userList->FetchRow()) {
    if ($u['fDeleted'] == 0 && $u['xPerson'] != $fm['xPerson']) {
        $usersSelect .= '<option value="'.$u['xPerson'].'" '.selectionCheck($u['xPerson'], $fm['xPersonOutOfOffice']).'>'.lg_admin_users_outofofficefwd.' '.$u['sFname'].' '.$u['sLname'].' '.($u['xPersonOutOfOffice'] ? '(Out of Office)' : '').'</option>';
    }
}

$avatar = new HS\Avatar\Avatar();
if($resourceid){
    $photoimage = $avatar->xPerson($fm['xPerson'])->domId('userphoto')->html();
}else{
    $photoimage = $avatar->renderImgTag(static_url().'/static/joypixels/1f464.svg');
}


/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<!-- tabs -->
<script type="text/javascript" language="JavaScript">
    Event.observe(window,"load",function(){
        $$(".tabs").each(function(tabs){
            new Control.Tabs(tabs);
        });
    });

    function notCatDefault(){
        if('.($resourceid && apiIsADefaultContact($resourceid) ? 'true' : 'false').'){
            hs_alert("'.lg_admin_users_er_default_cat.'");
            return false;
        }else{
            return true;
        }
    }

    jQuery( document ).ready(function( $ ) {
        $("#sEmailConfirm").on("blur", function(e){
            if($(this).val() != $("#sEmail").val()) {
                $(this).addClass("hdformerror").parent().find(".hderrorlabel").show();
            } else {
                $(this).removeClass("hdformerror").parent().find(".hderrorlabel").hide();
            }
        });
        $("#fNotifyEmail2").on("change", function(e){
            if($(this).is(":checked") && $("#sEmail2").val() == "") {
                hs_alert("'.lg_admin_users_nofyemail2_missing.'");
                $(this).prop("checked", false);
                return false;
            }
        });
    });
</script>';

$headscript .= markdown_setup('tSignature_HTML');

/*****************************************
API Tokens
*****************************************/
$tokenOutput = view('staff.api_tokens', [
    'tokens' => (! empty($resourceid)) ? \HS\User::find($resourceid)->tokens : new \Illuminate\Database\Eloquent\Collection(),
    'xPerson' => $fm['xPerson'],
])->render();

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($fb)) {
    $feedbackArea = $fb;
}
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

$pagebody .= '
<form enctype="multipart/form-data" name="userform" id="userform" class="" action="'.$basepgurl.'&action='.$formaction.'&resourceid='.$resourceid.'" method="POST" autocomplete="off">
'.csrf_field().'
<!-- The text and password here are to prevent browsers from auto filling login credentials because they ignore autocomplete="off"-->
<input type="text" style="display:none">
<input type="password" style="display:none">
<div id="feedbackbox">'.$feedbackArea.'</div>
 '.$datatable;

if ($showform) {
    $pagebody .= renderInnerPageheader($title);
    $pagebody .= '
    <div class="card padded">
        <div class="fr">
            <div class="label">
                <label for="sFname" class="datalabel req">'.lg_admin_users_firstname.'</label>
            </div>
            <div class="control">
                <input tabindex="100" name="sFname" id="sFname" type="text" size="40" value="'.formClean($fm['sFname']).'" class="'.errorClass('sFname').'">
                '.errorMessage('sFname').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="sLname" class="datalabel req">'.lg_admin_users_lastname.'</label>
            </div>
            <div class="control">
                <input tabindex="101" name="sLname" id="sLname" type="text" size="40" value="'.formClean($fm['sLname']).'" class="'.errorClass('sLname').'">
                '.errorMessage('sLname').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="" class="datalabel">'.lg_admin_users_photo.'</label>
            </div>
            <div class="control">
                <table>
                    <tr valign="top">
                        <td style="width:48px;padding-right:10px;">
                            <div style="display:flex;">
                                '.$photoimage.'
                                <div style="margin-left:10px;display:flex;align-items:center;">
                                    <a href="" onclick="$(\'xPersonPhotoId_upload\').toggle();return false;" class="btn inline-action" style="">'.lg_admin_users_uploadavatar.'</a>
                                    <a href="" onclick="$(\'xPerson_emoji\').toggle();$jq(\'#sEmoji\').focus();$jq(\'#sEmoji\').select();return false;" class="btn inline-action" style="">'.lg_admin_users_emojiavatar.'</a>
                                </div>
                            </div>
                            <input type="hidden" name="xPersonPhotoId" id="xPersonPhotoId" value="'.$fm['xPersonPhotoId'].'" />
                        </td>
                    </tr>
                    <tr id="xPersonPhotoId_upload" style="display:none;">
                        <td style="">
                            <div style="width:322px;padding:10px;margin-top:10px;background-color:rgba(0,0,0,0.1)">
                                <label class="datalabel" style="margin-bottom:10px;display:block;">'.lg_admin_users_avatars_upload.'</label>
                                <input type="file" name="photoupload"  />
                            </div>
                        </td>
                    </tr>
                    <tr id="xPerson_emoji" style="display:none;">
                        <td style="">
                            <div style="width:322px;padding:10px;margin-top:10px;background-color:rgba(0,0,0,0.1)">
                                <label class="datalabel" style="margin-bottom:10px;display:block;">'.lg_admin_users_avatars_emoji.'</label>
                                <input tabindex="101" name="sEmoji" id="sEmoji" type="text" maxlength="50" value="'.formClean($fm['sEmoji']).'" class="'.errorClass('sEmoji').'" style="width:36px;font-size:36px;">
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sEmail" class="datalabel req">'.lg_admin_users_email.'</label>
        </div>
        <div class="control">
            <input tabindex="102" name="sEmail" id="sEmail" type="text" size="25" value="'.formClean($fm['sEmail']).'" class="'.errorClass('sEmail').'">
            '.errorMessage('sEmail').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sEmail" class="datalabel req">'.lg_admin_users_email_confirm.'</label>
            <span class="hderrorlabel" style="display:none">'.lg_admin_users_er_email_confirm.'</span>
        </div>
        <div class="control">
            <input tabindex="102" name="sEmailConfirm" id="sEmailConfirm" type="text" size="25" value="'.formClean($fm['sEmailConfirm']).'" class="'.errorClass('sEmail').'" autocomplete="off">
            '.errorMessage('sEmail').'
        </div>
    </div>

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sPassword" class="datalabel '.(empty($resourceid) ? 'req' : '').'">'.lg_admin_users_password.'</label>
            '.(empty($resourceid) ? '<div class="info">' . lg_admin_users_password_info . '</div>' : '<div class="info">'.lg_admin_users_changepassword.'</div>').'
        </div>
        <div class="control">
            <input tabindex="103" name="sPassword" id="sPassword" type="'.(empty($resourceid) ? 'text' : 'password').'" size="25" value="" class="'.errorClass('sPassword').'" autocomplete="new-password">
            '.errorMessage('sPassword').'
        </div>
    </div>

    '.(isAdmin() && empty($pref) ? '
    <div class="hr"></div>

    <div class="fr">
            <div class="label"><label for="fUserType" class="datalabel">'.lg_admin_users_colut.'</label></div>
            <div class="control"><select name="fUserType" id="fUserType" tabindex="107">'.$groupSelect.'</select>
                '.errorMessage('fUserType').'</div>
        </div>' : '').'


    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label for="sUsername" class="datalabel">'.lg_admin_users_username.'</label>
            <div class="info">'.lg_admin_users_usernameex.'</div>
        </div>
        <div class="control">
            <input tabindex="104" name="sUsername" id="sUsername" type="text" size="25" value="'.formClean($fm['sUsername']).'" class="'.errorClass('sUsername').'">
            '.errorMessage('sUsername').'
        </div>
    </div>

    <fieldset>
        <div class="sectionhead">'.lg_admin_users_api_auth.'</div>

        '.$tokenOutput.'
    </fieldset>

    <fieldset>
        <div class="sectionhead">'.lg_admin_users_comm.'</div>

        <div class="fr">
            <div class="label"><label for="sEmail2" class="datalabel">'.lg_admin_users_email2.'</label></div>
            <div class="control">
                <input tabindex="107" name="sEmail2" id="sEmail2" type="text" size="25" value="'.formClean($fm['sEmail2']).'" class="'.errorClass('sEmail2').'">'.errorMessage('sEmail2').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="sPhone" class="datalabel">'.lg_admin_users_phone.'</label></div>
            <div class="control">
                <input tabindex="108" name="sPhone" id="sPhone" type="text" size="25" value="'.formClean($fm['sPhone']).'" class="'.errorClass('sPhone').'">'.errorMessage('sPhone').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="sSMS" class="datalabel">'.lg_admin_users_smsnum.' ('.lg_admin_users_smsnumex. ')</label></div>
            <div class="control">
                <div class="group vertical">
                    <input tabindex="109" name="sSMS" id="sSMS" type="text" size="25" style="width: 97%;" value="'.formClean($fm['sSMS']).'" class="'.errorClass('sSMS').'">
                    <select name="xSMSService" tabindex="107">'.$smsSelect.'</select>
                </div>
                '.errorMessage('sSMS').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label class="datalabel" for="xPersonOutOfOffice">'.lg_admin_users_outofoffice.'</label></div>
            <div class="control">
                <select tabindex="110" name="xPersonOutOfOffice" id="xPersonOutOfOffice">
                    <option value="0">'.lg_admin_users_outofofficedef.'</option>
                    <option value="-1" '.selectionCheck(-1, $fm['xPersonOutOfOffice']).'>'.lg_admin_users_outofofficefwd.' '.lg_inbox.'</option>
                    '.$usersSelect.'
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="datalabel" for="xPersonOutOfOffice">'.lg_admin_users_sig.'</label>
                <div class="info">'.lg_admin_users_sig_desc.'</div>
            </div>
            <div class="control">
                <textarea tabindex="111" name="tSignature" id="tSignature" cols="30" rows="4" style="" class="'.errorClass('tSignature').'">'.hs_htmlspecialchars($fm['tSignature']).'</textarea>
                '.errorMessage('tSignature').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="" class="datalabel">'.lg_admin_users_sig_html.'</label>
                <div class="info">'.lg_admin_users_sig_html_desc.'</div>
            </div>
            <div class="control">
                <div style="flex-direction: column;">
                    <textarea tabindex="112" name="tSignature_HTML" id="tSignature_HTML" rows="4" style="width: 97%;">'.hs_htmlspecialchars($fm['tSignature_HTML']).'</textarea>
                 </div>
            </div>
        </div>

    </fieldset>

    <fieldset class="fieldset">
        <div class="sectionhead">'.lg_admin_users_notification.'</div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="fNotifyEmail" class="datalabel">'.lg_admin_users_nofyemail.'</label>
            </div>
            <div class="control">
                <input tabindex="113" type="checkbox" name="fNotifyEmail" id="fNotifyEmail" class="checkbox" value="1" '.checkboxCheck(1, $fm['fNotifyEmail']).'>
                <label for="fNotifyEmail" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="fNotifyEmail2" class="datalabel">'.lg_admin_users_nofyemail2.'</label>
            </div>
            <div class="control">
                <input tabindex="114" type="checkbox" class="checkbox" name="fNotifyEmail2" id="fNotifyEmail2" value="1" '.checkboxCheck(1, $fm['fNotifyEmail2']).'>
                <label for="fNotifyEmail2" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="fNotifySMS" class="datalabel">'.lg_admin_users_nofysms.'</label>
            </div>
            <div class="control">
                <input tabindex="115" type="checkbox" class="checkbox" name="fNotifySMS" id="fNotifySMS" value="1" '.checkboxCheck(1, $fm['fNotifySMS']).'>
                <label for="fNotifySMS" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="fNotifySMSUrgent" class="datalabel">'.lg_admin_users_nofysmsurgent.'</label>
            </div>
            <div class="control">
                <input tabindex="116" type="checkbox" class="checkbox" name="fNotifySMSUrgent" id="fNotifySMSUrgent" value="1" '.checkboxCheck(1, $fm['fNotifySMSUrgent']).'>
                <label for="fNotifySMSUrgent" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>
        ';

        if ($showNotifyNewRequest) {
            $pagebody .= '
                <div class="fr">
                    <div class="label tdlcheckbox">
                        <label for="fNotifyNewRequest" class="datalabel">' . lg_admin_users_notifynewreq . '</label>
                        <div class="info">' . lg_admin_users_notifynewreqdesc . '</div>
                    </div>
                    <div class="control">
                        <input tabindex="117" type="checkbox" class="checkbox" name="fNotifyNewRequest" id="fNotifyNewRequest" value="1" ' . checkboxCheck(1, $fm['fNotifyNewRequest']) . '>
                        <label for="fNotifyNewRequest" class="switch"></label>
                    </div>
                </div>
            ';
        }

    $pagebody .= '

    </fieldset>

    <fieldset class="fieldset">
        <div class="sectionhead">'.lg_admin_users_prefs.'</div>

        <div class="fr">
            <div class="label">
                <label for="sEmail" class="datalabel">'.lg_admin_users_darkmode.'</label>
            </div>
            <div class="control">
                <input tabindex="118" type="checkbox" name="fDarkMode" id="fDarkMode" class="checkbox" value="1" '.checkboxCheck(1, $fm['fDarkMode']).'>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="fDefaultToPublic" class="datalabel">'.lg_admin_users_defpublic.'</label>
            </div>
            <div class="control">
                <input tabindex="119" type="checkbox" class="checkbox" name="fDefaultToPublic" id="fDefaultToPublic" value="1" '.(empty($resourceid) ? 'checked' : checkboxCheck(1, $fm['fDefaultToPublic'])).'>
                <label for="fDefaultToPublic" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="fHideWysiwyg" class="datalabel">'.lg_admin_users_hidewysy.'</label>
            </div>
            <div class="control">
                <input tabindex="120" type="checkbox" class="checkbox" name="fHideWysiwyg" id="fHideWysiwyg" value="1" '.checkboxCheck(1, $fm['fHideWysiwyg']).'>
                <label for="fHideWysiwyg" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="fHideImages" class="datalabel">'.lg_admin_users_noembed.'</label>
            </div>
            <div class="control">
                <input tabindex="121" type="checkbox" class="checkbox" name="fHideImages" id="fHideImages" value="1" '.checkboxCheck(1, $fm['fHideImages']).'>
                <label for="fHideImages" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="fKeyboardShortcuts" class="datalabel">'.lg_admin_users_shortcuts.' (<a href="'.route('admin', ['pg' => 'shortcuts.popup']).'" target="_blank">'.lg_details.'</a>)</label>
            </div>
            <div class="control">
                <input tabindex="122" type="checkbox" class="checkbox" name="fKeyboardShortcuts" id="fKeyboardShortcuts" value="1" '.checkboxCheck(1, $fm['fKeyboardShortcuts']).'>
                <label for="fKeyboardShortcuts" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="iRequestHistoryLimit" class="datalabel">'.lg_admin_users_reqhistorylimit.'</label>
                <div class="info">'.lg_admin_users_reqhistorylimitex.'</div>
            </div>
            <div class="control">
                <input tabindex="123" type="text" class="input-80" name="iRequestHistoryLimit" id="iRequestHistoryLimit" size="6" value="'.$fm['iRequestHistoryLimit'].'">
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="fReturnToReq" class="datalabel">'.lg_admin_users_returntorequest.'</label></div>
            <div class="control">
                <select name="fReturnToReq" id="fReturnToReq" tabindex="124">
                    <option value="0" '.selectionCheck(0, $fm['fReturnToReq']).'>'.lg_admin_users_returntorequest0.'</option>
                    <option value="1" '.selectionCheck(1, $fm['fReturnToReq']).'>'.lg_admin_users_returntorequest1.'</option>
                    <option value="2" '.selectionCheck(2, $fm['fReturnToReq']).'>'.lg_admin_users_returntorequest2.'</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label"><label for="fRequestHistoryView" class="datalabel">'.lg_admin_users_rhview.'</label></div>
            <div class="control">
                <select name="fRequestHistoryView" id="fRequestHistoryView" tabindex="125">
                    <option value="1" '.selectionCheck(1, $fm['fRequestHistoryView']).'>'.lg_admin_users_rhview1.'</option>
                    <option value="4" '.selectionCheck(4, $fm['fRequestHistoryView']).'>'.lg_admin_users_rhview4.'</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>';
    if (hs_setting('cHD_HTMLEMAILS')) {
        $pagebody .= '
            <div class="fr">
                <div class="label">
                    <label for="sHTMLEditor" class="datalabel">'.lg_admin_users_htmlemail_editor.'</label>
                    <div class="info">'.lg_admin_users_htmlemail_editorex.'</div>
                </div>
                <div class="control">
                    <select name="sHTMLEditor" id="sHTMLEditor" tabindex="126">
                        <option value="" '.selectionCheck('', $fm['sHTMLEditor']).'>'.lg_admin_users_htmlemail_default.'</option>
                        <option value="wysiwyg" '.selectionCheck('wysiwyg', $fm['sHTMLEditor']).'>'.lg_admin_users_htmlemail_wysiwyg.'</option>
                        <option value="markdown" '.selectionCheck('markdown', $fm['sHTMLEditor']).'>'.lg_admin_users_htmlemail_markdown.'</option>
                    </select>
                </div>
            </div>';
    } else {
        $pagebody .= '<input type="hidden" name="sHTMLEditor" value="'.$fm['sHTMLEditor'].'" />';
    }
    $pagebody .= '
    </fieldset>
    ';

    if (isAdmin() && empty($pref)) {
        $pagebody .= '
            <fieldset class="fieldset">
                <div class="sectionhead">'.lg_admin_users_assigncats.'</div>
                <div class="sectiondesc">'.lg_admin_users_assigncatsdesc.'</div>
                '.$catsSelect.'
            </fieldset>
        ';
    }

    $pagebody .= '</div>';

    $pagebody .= '
        <div class="button-bar space">
            <div>
                <button type="submit" name="submit" class="btn accent">'.$button.'</button>
                '.((isAdmin() && empty($resourceid)) ? '<input type="checkbox" name="emailnewuser" id="emailnewuser" value="1" class="formbuttondivbg" checked> <label for="emailnewuser" class="datalabel" style="display:inline;">'.lg_admin_users_emailnewuser.'</label>' : '').'
            </div>
            '.$delbutton.'
        </div>
    ';

} else { //end license check
    $text = '<div style="display:flex;justify-content: space-between;margin: 20px 0;" id="notification-'.$notification->id.'">
                <div>
                    '.lg_admin_users_nomoreusers.'
                    <a class="action" href="https://store.helpspot.com">buy now</a>
                    or <a class="action" href="https://www.helpspot.com/talk-to-sales">contact sales</a>
                </div>
            </div>';
    $pagebody .= displaySystemBox($text);
}

$pagebody .= '</form>';
