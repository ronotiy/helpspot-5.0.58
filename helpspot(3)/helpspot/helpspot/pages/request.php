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
include cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
include cBASEPATH.'/helpspot/lib/api.kb.lib.php';
include cBASEPATH.'/helpspot/lib/class.requestupdate.php';
include cBASEPATH . '/helpspot/lib/api.thermostat.lib.php';

use HS\Domain\Workspace\Request;
use HS\FeedbackLookup;

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
//Handle regular reqid's or accesskeys
if (isset($_REQUEST['reqid'])) {
    $t = parseAccessKey($_REQUEST['reqid']);
    $reqid = $t['xRequest'];
} else {
    $reqid = '';
}

//Check if this is a merged request, if so redirect
if ($reqid && $merged_id = apiCheckIfMerged($reqid)) {
    return redirect()->route('admin', ['pg' => 'request', 'reqid' => $merged_id]);
}

//Handle next/prev
if (isset($_GET['next']) || isset($_GET['prev'])) {
    $last_queue = trim($_COOKIE['last_queue']);

    //Get id's for this filter
    if (is_numeric($last_queue)) {
        $filters = apiGetAllFilters($user['xPerson'], 'all');
        $ft = new hs_filter($filters[$last_queue]);
    } else {
        $ft = new hs_filter();
        $ft->useSystemFilter($last_queue);
    }
    $reqids = $ft->outputReqIDs();

    $currentElement = $reqid;
    $firstElement = current($reqids);
    $lastElement = $reqids[count($reqids) - 1];

    $currentKey = array_search($currentElement, $reqids);
    $currentValue = $reqids[$currentKey];

    $previousValue = '';
    $nextValue = '';
    if (isset($_GET['next'])) {
        if ($currentElement != $lastElement) {
            $nextKey = $currentKey + 1;
            $nextValue = $reqids[$nextKey];
            return redirect()->route('admin', ['pg' => 'request', 'reqid' => $nextValue]);
        } else { //Trying to go past last reqid
            return redirect()->route('admin', ['pg' => 'workspace', 'show' => $last_queue]);
        }
    }

    if (isset($_GET['prev'])) {
        if ($currentElement != $firstElement) {
            $previousKey = $currentKey - 1;
            $previousValue = $reqids[$previousKey];
            return redirect()->route('admin', ['pg' => 'request', 'reqid' => $previousValue]);
        } else { //Trying to go back past first reqid
            return redirect()->route('admin', ['pg' => 'workspace', 'show' => $last_queue]);
        }
    }
}

$tab = 'nav_workspace';
$subtab = '';
//$from		 = isset($_GET['from']) ? $_GET['from'] : '';
$reqhisid = isset($_GET['reqhisid']) ? $_GET['reqhisid'] : '';
$customFields = $GLOBALS['customFields'];
$customfieldsdisplay = '';
$tindex = 200;
$feedbackArea = '';
$hidePageFrame = isset($_GET['hideframe']) ? 1 : '';
$unpublic = isset($_GET['unpublic']) ? 1 : '';
$makepublic = isset($_GET['makepublic']) ? 1 : '';
$delreminder = isset($_GET['delreminder']) && is_numeric($_GET['delreminder']) ? $_GET['delreminder'] : '';
$last_queue = isset($_COOKIE['last_queue']) && ! empty($_COOKIE['last_queue']) ? trim($_COOKIE['last_queue']) : $user['sWorkspaceDefault'];
$reopen = isset($_POST['reopen']) ? 1 : '';
$trash = isset($_GET['trash']) ? 1 : '';
$remove_from_trash = isset($_GET['remove_from_trash']) ? 1 : '';
$frominbox = isset($_GET['frominbox']);
$batch = isset($_GET['batch']) && (perm('fCanBatchRespond')) ? $_GET['batch'] : false;
$directlink = isset($_GET['xRequestHistory']);
$htmldirect = isset($_GET['htmldirect']);
$headerloc = '';
$urgentheader = '';
$reminderlist = '';
$onload = '';
$openreqcount = 0;
$editor_type = 'none';
$pagebody = '';
$headscript = '';

//See if we need to load an editor
if (hs_setting('cHD_HTMLEMAILS')) {
    if ((hs_setting('cHD_HTMLEMAILS_EDITOR') == 'wysiwyg' && $user['sHTMLEditor'] != 'markdown') || $user['sHTMLEditor'] == 'wysiwyg') {
        $editor_type = 'wysiwyg';
    }
    if ((hs_setting('cHD_HTMLEMAILS_EDITOR') == 'markdown' && $user['sHTMLEditor'] != 'wysiwyg') || $user['sHTMLEditor'] == 'markdown') {
        $editor_type = 'markdown';
    }
    //iPhone/iPod check, if true then override above and use markdown
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false ||
       strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false ||
       strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== false) {
        $editor_type = 'markdown';
    }
}

$fm['fOpenedVia'] = isset($_POST['fOpenedVia']) ? $_POST['fOpenedVia'] : hs_setting('cHD_CONTACTVIA');
$fm['xOpenedViaId'] = isset($_POST['xOpenedViaId']) ? $_POST['xOpenedViaId'] : 0;
$fm['xMailboxToSendFrom'] = isset($_POST['xMailboxToSendFrom']) ? $_POST['xMailboxToSendFrom'] : 0;
$fm['xPersonOpenedBy'] = isset($_POST['xPersonOpenedBy']) ? $_POST['xPersonOpenedBy'] : $user['xPerson'];
$fm['xPersonAssignedTo'] = isset($_POST['xPersonAssignedTo']) ? $_POST['xPersonAssignedTo'] : 0;
$fm['fOpen'] = isset($_POST['fOpen']) ? $_POST['fOpen'] : 1;
$fm['xStatus'] = isset($_REQUEST['xStatus']) ? $_REQUEST['xStatus'] : hs_setting('cHD_STATUS_ACTIVE');
$fm['xCategory'] = isset($_REQUEST['xCategory']) ? $_REQUEST['xCategory'] : 0;
$fm['dtGMTOpened'] = isset($_POST['dtGMTOpened']) ? $_POST['dtGMTOpened'] : date('U');
$fm['sTitle'] = isset($_REQUEST['sTitle']) ? $_REQUEST['sTitle'] : '';
$fm['fUrgent'] = isset($_POST['fUrgent']) ? $_POST['fUrgent'] : 0;
$fm['fTrash'] = isset($_POST['fTrash']) ? $_POST['fTrash'] : 0;
$fm['dtGMTTrashed'] = isset($_POST['dtGMTTrashed']) ? $_POST['dtGMTTrashed'] : 0;
$fm['fPublic'] = isset($_POST['fPublic']) ? $_POST['fPublic'] : 0;
$fm['external_note'] = isset($_POST['external_note']) ? $_POST['external_note'] : 0;
$fm['iTimerSeconds'] = isset($_POST['iTimerSeconds']) ? $_POST['iTimerSeconds'] : 0;
$fm['emailfrom'] = isset($_POST['emailfrom']) ? $_POST['emailfrom'] : hs_setting('cHD_NOTIFICATIONEMAILACCT');
$fm['ccstaff'] = isset($_POST['ccstaff']) ? implode(',', $_POST['ccstaff']) : '';	//legacy: was kept as comma list. Since now posted as array, need to turn back to comma list for use and storage
$fm['emailccgroup'] = isset($_REQUEST['emailccgroup']) ? $_REQUEST['emailccgroup'] : '';
$fm['emailbccgroup'] = isset($_REQUEST['emailbccgroup']) ? $_REQUEST['emailbccgroup'] : '';
$fm['emailccgroup_inactive'] = isset($_POST['emailccgroup_inactive']) ? $_POST['emailccgroup_inactive'] : '';
$fm['emailbccgroup_inactive'] = isset($_POST['emailbccgroup_inactive']) ? $_POST['emailbccgroup_inactive'] : '';
$fm['emailtogroup'] = isset($_POST['emailtogroup']) ? $_POST['emailtogroup'] : '';
$fm['send_email'] = isset($_POST['send_email']) ? $_POST['send_email'] : 1;

$fm['sUserId'] = isset($_REQUEST['sUserId']) ? $_REQUEST['sUserId'] : '';
$fm['sFirstName'] = isset($_REQUEST['sFirstName']) ? $_REQUEST['sFirstName'] : '';
$fm['sLastName'] = isset($_REQUEST['sLastName']) ? $_REQUEST['sLastName'] : '';
$fm['sEmail'] = isset($_REQUEST['sEmail']) ? $_REQUEST['sEmail'] : '';
$fm['sPhone'] = isset($_REQUEST['sPhone']) ? $_REQUEST['sPhone'] : '';

if (hs_isreallyempty($_REQUEST['tBody'])) {
    $fm['tBody'] = '';
} else {
    $fm['tBody'] = isset($_REQUEST['tBody']) ? trim($_REQUEST['tBody']) : '';
}

$fm['fNoteIsHTML'] = isset($_POST['fNoteIsHTML']) ? $_POST['fNoteIsHTML'] : (hs_setting('cHD_HTMLEMAILS') ? 1 : 0);
$fm['note_is_markdown'] = isset($_POST['note_is_markdown']) ? $_POST['note_is_markdown'] : 0;
$fm['tEmailHeaders'] = isset($_POST['tEmailHeaders']) ? $_POST['tEmailHeaders'] : '';
$fm['reportingTags'] = isset($_POST['reportingTags']) ? $_POST['reportingTags'] : '';
$fm['sRequestPassword'] = isset($_POST['sRequestPassword']) ? $_POST['sRequestPassword'] : '';

$fm['sub_create'] = isset($_POST['sub_create']) ? $_POST['sub_create'] : '';
$fm['sub_create_close'] = isset($_POST['sub_create_close']) ? $_POST['sub_create_close'] : '';
$fm['sub_update'] = isset($_POST['sub_update']) ? $_POST['sub_update'] : '';
$fm['sub_updatenclose'] = isset($_POST['sub_updatenclose']) ? $_POST['sub_updatenclose'] : '';

$fm['attachment'] = isset($_POST['attachment']) ? $_POST['attachment'] : false;
$fm['reattach'] = isset($_POST['reattach']) ? $_POST['reattach'] : false;
$fm['subscribe_all_ccstaff'] = isset($_POST['subscribe_all_ccstaff']) ? $_POST['subscribe_all_ccstaff'] : '';

//Timer
$fm['tracker'] = [];
$fm['tracker']['xPerson'] = hs_numeric($_POST, 'xPerson') ? $_POST['xPerson'] : 0;
$fm['tracker']['tDescription'] = isset($_POST['tDescription']) ? $_POST['tDescription'] : '';
$fm['tracker']['tTime'] = isset($_POST['tTime']) ? $_POST['tTime'] : 0;
$fm['tracker']['iMonth'] = hs_numeric($_POST, 'iMonth') ? $_POST['iMonth'] : 1;
$fm['tracker']['iDay'] = hs_numeric($_POST, 'iDay') ? $_POST['iDay'] : 1;
$fm['tracker']['iYear'] = hs_numeric($_POST, 'iYear') ? $_POST['iYear'] : date('Y');
$fm['tracker']['dtGMTDate'] = hs_numeric($_POST, 'dtGMTDate') ? $_POST['dtGMTDate'] : 0;
$fm['tracker']['fBillable'] = hs_numeric($_POST, 'fBillable') ? $_POST['fBillable'] : 0;

$fm['vmode'] = isset($_REQUEST['vmode']) ? $_REQUEST['vmode'] : '';
$fm['fb'] = isset($_GET['fb']) ? $_GET['fb'] : 0;

// Setup custom fields
if (is_array($customFields) && ! empty($customFields)) {
    foreach ($customFields as $k=>$v) {
        $custid = 'Custom'.$v['fieldID'];
        $fm[$custid] = isset($_REQUEST[$custid]) ? $_REQUEST[$custid] : '';
    }
}

//Secure from guests
if (perm('fCanViewOwnReqsOnly') && ! empty($reqid)) {
    $greq = apiGetRequest($reqid);
    if ($greq['xPersonAssignedTo'] != $user['xPerson']) {
        return redirect()->route('admin', ['pg' => 'workspace']);
    }
}

//get list of staff
$staffList = apiGetAllUsers();
$staffList = rsToArray($staffList, 'xPerson', false);

//Setup batch if doing a batch response
if ($batch) {
    //Create table to display requests in batch
    $ft = new hs_filter();
    $ft->filterDef['displayColumns'] = ['xRequest', 'iLastReplyBy', 'fOpenedVia', 'fullname', 'reqsummary', 'dtGMTOpened'];
    $ft->filterDef['anyall'] = 'any';
    $ft->filterDef['xRequest'] = $batch;
    $ftrs = $ft->outputResultSet();

    foreach ($ft->filterDef['displayColumns'] as $nk=>$v) {
        $cols[$v] = $GLOBALS['filterCols'][$v];
    }

    //Add custom column
    $cols['delete_batch_request'] = ['type'=>'link', 'label'=>'', 'sort'=>0, 'nowrap'=>true, 'width'=>'20',
                                                  'code'=>'<a href="" onClick="$(\'batch_request_%s\').remove();$(parentNode.parentNode).remove();return false;"><img src="'.static_url().'/static/img5/remove.svg" alt="'.lg_request_batchremove.'" title="'.lg_request_batchremove.'" border="0" /></a>',
                                                  'fields'=>'xRequest', 'linkfields'=>['xRequest'], ];

    $batchtable = recordSetTable($ftrs,$cols,
                        //options
                        ['width'=>'100%',
                                'from_run_filter'=>true,
                                'title'=>lg_request_batchlist, ], route('admin', ['pg' => 'request']));
    //Add hidden field with batch id's
    foreach ($batch as $k=>$id) {
        $batchtable .= '<input type="hidden" name="batch[]" id="batch_request_'.$id.'" value="'.$id.'" />';
    }
}

//IF TURNING A REQUEST HISTORY INTO A REQUEST
if (isset($_GET['convertToRequest']) && is_numeric($_GET['xRequestHistory'])) {
    $request = apiGetRequest($reqid);
    $note = apiGetHistoryEvent($_GET['xRequestHistory']);

    $msgdate = time();

    //Create request
    $rc = apiAddEditRequest(['fOpenedVia'		=>$request['fOpenedVia'],
                             'xOpenedViaId'		=>$request['xOpenedViaId'],
                             'mode'				=>'add',
                             'xPersonOpenedBy'	=>$note['xPerson'],
                             'xPersonAssignedTo'=>$request['xPersonAssignedTo'],
                             'xCategory'		=>$request['xCategory'],
                             'dtGMTOpened'		=>$msgdate,
                             'tBody'			=>$note['tNote'],
                             'tLog'				=>$note['tLog'],
                             'tEmailHeaders'	=>$note['tEmailHeaders'],
                             'fPublic'			=>$note['fPublic'],
                             'fNoteIsHTML'		=>$note['fNoteIsHTML'],
                             'sUserId'			=>$request['sUserId'],
                             'sFirstName'		=>$request['sFirstName'],
                             'sLastName'		=>$request['sLastName'],
                             'sEmail'			=>$request['sEmail'],
                             'sPhone'			=>$request['sPhone'], ], 0, __FILE__, __LINE__);

    if (isset($rc['xRequest'])) {
        //Log the conversion
        apiAddRequestHistory([
                'xRequest' => $rc['xRequest'],
                'xPerson' => $user['xPerson'],
                'dtGMTChange' => $msgdate + 1,
                'tLog' => '',
                'tNote' => sprintf(lg_lookup_25, '<a href="admin?pg=request&reqid='.$reqid.'">'.$reqid.'</a>'),
                'fNoteIsHTML' => 1,
                'fNoteIsClean' => true,
            ]);

        apiAddRequestHistory([
                'xRequest' => $reqid,
                'xPerson' => $user['xPerson'],
                'dtGMTChange' => $msgdate + 1,
                'tLog' => '',
                'tNote' => sprintf(lg_lookup_26, $_GET['xRequestHistory'], '<a href="admin?pg=request&reqid='.$rc['xRequest'].'">'.$rc['xRequest'].'</a>'),
                'fNoteIsHTML' => 1,
                'fNoteIsClean' => true,
            ]);

        // Copy attachments related to that request history item
        $originalRequestHistoryDocuments = \HS\Domain\Workspace\Document::noBlob()
            ->where('xRequestHistory', $note['xRequestHistory'])
            ->get();

        $msgFiles = [];
        foreach($originalRequestHistoryDocuments as $doc) {
            /** @var $doc \HS\Domain\Workspace\Document */
            $msgFiles[0]['name'] = $doc->sFilename;
            $msgFiles[0]['mimetype'] = $doc->sFileMimeType;
            $msgFiles[0]['content-id'] =  $doc->sCID;
            $msgFiles[0]['body'] = $doc->getFile()->getBody();

            //Save file to new request
            apiAddDocument($rc['xRequest'], $msgFiles, $rc['xRequestHistory'], __FILE__, __LINE__);
        }
    }

    return redirect()->route('admin', ['pg' => 'request', 'reqid' => $rc['xRequest']]);
}

/*****************************************
SIMPLE ACTIONS - BEFORE CHECKS
*****************************************/
/************ REOPEN CLOSED REQ *************/
if (! empty($reopen)) {
    $origReq = apiGetRequest($reqid);
    $origUser = apiGetUser($origReq['xPersonAssignedTo']);
    //If original assignee available then set back else return to inbox
    $cat = apiGetCategory($origReq['xCategory']);
    $catpeps = hs_unserialize($cat['sPersonList']);
    if ($origUser['fDeleted'] == 0 && $cat['fDeleted'] == 0 && in_array($origUser['xPerson'], $catpeps)) {
        $origReq['xPersonAssignedTo'] = $origUser['xPerson'];
    } else {
        $origReq['xPersonAssignedTo'] = 0;
    }

    $origReq['fOpen'] = 1;
    $origReq['xStatus'] = hs_setting('cHD_STATUS_ACTIVE');
    $origReq['dtGMTOpened'] = date('U');	//current dt
    $update = new requestUpdate($reqid, $origReq, $user['xPerson'], __FILE__, __LINE__);
    $reqResult = $update->checkChanges();
    return redirect()->route('admin', ['pg' => 'request', 'reqid' => $reqid]);
}

/************ MOVE TO TRASH *************/
if ($trash and perm('fCanManageTrash')) {
    $origReq = apiGetRequest($reqid);

    $origReq['fTrash'] = 1;
    $origReq['dtGMTTrashed'] = date('U');
    $origReq['dtGMTOpened'] = date('U');	//current dt
    $update = new requestUpdate($reqid, $origReq, $user['xPerson'], __FILE__, __LINE__);
    $reqResult = $update->checkChanges();
    return redirect()->route('admin', ['pg' => 'request', 'reqid' => $reqid]);
}

/************ REMOVE FROM TRASH *************/
if ($remove_from_trash) {
    $origReq = apiGetRequest($reqid);

    $origReq['fTrash'] = 0;
    $origReq['dtGMTTrashed'] = 0;
    $origReq['dtGMTOpened'] = date('U');	//current dt
    $update = new requestUpdate($reqid, $origReq, $user['xPerson'], __FILE__, __LINE__);
    $reqResult = $update->checkChanges();
    return redirect()->route('admin', ['pg' => 'request', 'reqid' => $reqid]);
}

/************ PUBLIC/UNPUBLIC *************/
if (! empty($unpublic) && ! empty($reqhisid)) {
    $pubres = apiUnPublic($reqhisid);
    if ($pubres) {
        return redirect()
            ->route('admin', ['pg' => 'request', 'reqid' => $reqid])
            ->with('feedback', lg_request_fb_unpublic);
    } else {
        $formerrors['errorBoxText'] = lg_request_er_unpublic;
    }
}

if (! empty($makepublic) && ! empty($reqhisid)) {
    $pubres = apiMakePublic($reqhisid);
    if ($pubres) {
        return redirect()
            ->route('admin', ['pg' => 'request', 'reqid' => $reqid])
            ->with('feedback', FeedbackLookup::byFb('request', 11));
    } else {
        $formerrors['errorBoxText'] = lg_request_er_makepublic;
    }
}

/************ DELETE REMINDER *************/
if (! empty($delreminder)) {
    $delrem = apiDeleteReminder($delreminder);
    if ($delrem) {
        return redirect()
            ->route('admin', ['pg' => 'request', 'reqid' => $reqid])
            ->with('feedback', FeedbackLookup::byFb('request', 10));
    } else {
        $formerrors['errorBoxText'] = lg_request_er_delreminder;
    }
}

/*****************************************
ACTIONS
*****************************************/
if ($fm['vmode'] == 1) {
	$result = apiProcessRequest($reqid, $fm, $_FILES, __FILE__, __LINE__);
    if (isset($result['fb'])) {	//if fb number exists then things went OK
        //Redirect
        if ($fm['fOpen'] == 0) {
            if ($user['fReturnToReq'] == 2) { //move to next request in queue
                $nextValue = false;
                $last_queue = trim($_COOKIE['last_queue']);

                //Get id's for this filter
                if (is_numeric($last_queue)) {
                    $filters = apiGetAllFilters($user['xPerson'], 'all');
                    $ft = new hs_filter($filters[$last_queue]);
                } else {
                    $ft = new hs_filter();
                    $ft->useSystemFilter($last_queue);
                }
                $reqids = $ft->outputReqIDs();

                $currentElement = $reqid;
                $firstElement = $reqids[0];
                $lastElement = $reqids[count($reqids) - 1];

                if (count($reqids) > 1) {
                    $currentKey = array_search($currentElement, $reqids);
                    $nextValue = $reqids[$currentKey];
                } elseif (count($reqids) == 1) {
                    $nextValue = $firstElement;
                }

                if ($nextValue) {
                    return redirect()->route('admin', ['pg' => 'request', 'reqid' => $nextValue]);
                } else {
                    return redirect()->route('admin', ['pg' => 'workspace', 'show' => $last_queue]);
                }
            } elseif ($user['fReturnToReq'] == 1) { //return to request
                return redirect()
                        ->route('admin', ['pg' => 'request', 'reqid' => $result['reqid']])
                        ->with('feedback', FeedbackLookup::byFb('request', $result['fb']));
            } else { //return to workspace
                return redirect()
                    ->route('admin', ['pg' => 'workspace', 'show' => $last_queue])
                    ->with('feedback', FeedbackLookup::byFb('request', $result['fb']));
            }
        } else {
            return redirect()
                ->route('admin', ['pg' => 'request', 'reqid' => $result['reqid']])
                ->with('feedback', FeedbackLookup::byFb('request', $result['fb']));
        }
    } else {					//array of errors
        $formerrors = $result;
        setErrors($formerrors);
    }
}

/*****************************************
PAGE TEMPLATE COMPONENTS
*****************************************/
// Setup page if looking at request vs new request
if (! empty($reqid)) {
    $tempfm = apiGetRequest($reqid);
    if (is_array($tempfm)) {
        $fm = array_merge($fm, $tempfm);
    } else {
        //ID does not exist so redirect to static page to show no id message
        return redirect()->route('admin', ['pg' => 'request.static', 'reqid' => $reqid]);
    }

    //REDIRECT CLOSED REQUESTS TO STATIC
    //As of v3 closed requests are sent to the new static page
    $redirectQuery = ['pg' => 'request.static', 'reqid' => $reqid];
    if (isset($_GET['emailerror'])) {
        $redirectQuery = array_merge($redirectQuery, ['emailerror' => $_GET['emailerror']]);
    }
    if ($fm['fOpen'] == 0) {
        return redirect()->route('admin', $redirectQuery);
    }

    //find portal password
    $portal_name = apiGetPortalName($fm['xPortal'], $fm['fOpenedVia']);

    //If we're in limited access mode make sure only a valid user can see the request
    //Only needed for L2 since guests are already restricted to only requests assigned to them
    if (perm('fLimitedToAssignedCats')) {
        $cats = apiGetUserCats($user['xPerson']);
        if (! in_array($fm['xCategory'], $cats)) {
            return redirect()->route('admin', ['pg' => 'workspace']);
        }
    }

    //Set focus on existing request to note box
    $afterload = 'focus_note_body("tBody");';

    //Find CC and BCC emails
    $email_groups = getEmailGroups($fm);

    //inbox logic to prevent multiple people from answering same request
    if ($frominbox) {
        //check to see if already assigned
        if ($fm['xPersonAssignedTo'] != 0) {
            //already picked up by someone else so notify user
            $frmuser = apiGetUser($fm['xPersonAssignedTo']);

            $headscript .= '
				<script type="text/javascript" language="JavaScript">
				$jq().ready(function(){
					hs_alert("'.hs_jshtmlentities(lg_request_fb_fromhdinprocess.$frmuser['sFname'].' '.$frmuser['sLname']).'");
				});
				</script>';
        } else {
            //not picked up so assign to this one
            $fm['xPersonAssignedTo'] = $user['xPerson'];
            $fm['dtGMTOpened'] = date('U');	//current dt

            //Keep in cat if user is in that cat, else set back to 0
            $cat = apiGetCategory($fm['xCategory']);
            $catpeps = hs_unserialize($cat['sPersonList']);
            if (! in_array($user['xPerson'], $catpeps)) {
                $fm['xCategory'] = 0;
                $fm['reportingTags'] = [];
            } else {
                $fm['reportingTags'] = array_keys(apiGetRequestRepTags($reqid));
            }

            $update = new requestUpdate($reqid, $fm, $user['xPerson'], __FILE__, __LINE__);
            $update->notify = false;
            $reqResult = $update->checkChanges();
            return redirect()->route('admin', ['pg' => 'request', 'reqid' => $reqid]);
        }
    }

    //If using Take It button on a filter
    if (isset($_GET['takeitfilter'])) {

        //check to see if already assigned, if so then notify. If not do further checks.
        if ($fm['xPersonAssignedTo'] != 0 && hs_setting('cHD_TAKEIT_DOCHECK') == 1) {
            //already picked up by someone else so notify user
            $frmuser = apiGetUser($fm['xPersonAssignedTo']);
            $formerrors['errorBoxText'] = lg_request_fb_fromhdinprocess.$frmuser['sFname'].' '.$frmuser['sLname'];
            $hidepage = true;
        } else {
            //Keep in cat if user is in that cat, else set back to 0
            $cat = apiGetCategory($fm['xCategory']);
            $catpeps = hs_unserialize($cat['sPersonList']);
            if (! in_array($user['xPerson'], $catpeps)) {
                $fm['xCategory'] = 0;
                $fm['reportingTags'] = [];
            } else {
                $fm['reportingTags'] = array_keys(apiGetRequestRepTags($reqid));
            }

            $fm['xPersonAssignedTo'] = $user['xPerson'];
            $fm['dtGMTOpened'] = date('U');	//current dt

            $update = new requestUpdate($reqid, $fm, $user['xPerson'], __FILE__, __LINE__);
            $update->notify = false; //notify below instead
            $reqResult = $update->checkChanges();
            return redirect()
                ->route('admin', ['pg' => 'request', 'reqid' => $reqid])
                ->with('feedback', lg_request_fb_requpdated);
        }
    }

    //Public email default
    if (hs_empty($fm['sTitle'])) {
        $fm['sTitle'] = lg_request_subjectdefault;
    } elseif (lg_mailre != '' && utf8_substr($fm['sTitle'], 0, utf8_strlen(lg_mailre.': ')) != lg_mailre.': ') {
        $fm['sTitle'] = lg_mailre.': '.$fm['sTitle'];
    }

    /*****************************************
    READ/UNREAD
    *****************************************/
    if ($fm['xPersonAssignedTo'] == $user['xPerson']) {
        updateReadUnread($reqid);
    }
    /*****************************************/

    $pagetitle = $reqid.' - '.lg_request_pagetitleedit;

    $reqSubscribers = apiGetRequestSubscribers($reqid);

    $buttons = '<button tabindex="303" type="submit" name="sub_update" id="sub_update" value="sub_update" class="btn inline-action">'.lg_request_update.'</button>';
    $buttons .= '<button tabindex="304" type="submit" name="sub_updatenclose" id="sub_updatenclose" value="sub_updatenclose" class="btn inline-action" onClick="close_clicked=true;">'.lg_request_updatenclose.'</button>';

    //Make list of subscribers
    $reqSubscribersList = '';
    foreach ($reqSubscribers as $k=>$v) {
        $reqSubscribersList .= '<span class="notification_item">'.$staffList[$v]['sFname'].' '.$staffList[$v]['sLname'].'</span>';
    }

    //Recover a draft
    $drafts = apiGetDrafts($reqid, $user['xPerson']);

    //Push request
    $push_classes = listRequestPushClasses();

    if (count($push_classes)) {
        $pushedlist = showPushesByReq($reqid);

        $push_options = '<select tabindex="-1" id="push_option">';
        foreach ($push_classes as $k=>$v) {
            $push_options .= '<option value="'.hs_htmlspecialchars($v['name']).'">'.$v['name'].'</option>';
        }
        $push_options .= '</select>';

        $pushui = '
                <div class="card" style="padding:20px;margin-top:30px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <div style="font-weight:bold;">'.lg_request_reqpush.'</div>
                        <button type="button" onclick="do_push();" class="btn accent inline-action">'.lg_request_push.'</button>
                    </div>

                    <div class="field-wrap">
                        <label class="datalabel">'.lg_request_pushselect.'</label>
                        '.$push_options.'
                    </div>

                    <div class="field-wrap">
                        <label class="datalabel">'.lg_request_pushcomment.'</label>
                        <textarea name="tComment" id="tComment" class="" cols="" rows="2" style=""></textarea>
                    </div>

                    <div id="pushes_list" style="display:flex;justify-content:center;">'.$pushedlist.'</div>
                </div>';
    }

    //Reminders
    $remindersrs = apiGetRemindersByReq($reqid, $user['xPerson'], __FILE__, __LINE__);
    $remindersct = $remindersrs->RecordCount();
    if ($remindersct > 0) {
        $reminderct_label = 'request_calendar_button_active';
    } else {
        $reminderct_label = '';
    }

    //Find out how many open requests this customer has
    $openreqcountrs = apiRequestHistorySearch([
                                                    'search_type'=>hs_setting('cHD_DEFAULT_HISTORYSEARCH'),
                                                    'sUserId'=>$fm['sUserId'],
                                                    'sFirstName'=>$fm['sFirstName'],
                                                    'sLastName'=>$fm['sLastName'],
                                                    'sEmail'=>$fm['sEmail'],
                                                    'sPhone'=>$fm['sPhone'],
                                                    ], ['fOpen'], __FILE__, __LINE__);

    if (hs_rscheck($openreqcountrs)) {
        while ($rcrow = $openreqcountrs->FetchRow()) {
            if ($rcrow['fOpen'] == 1) {
                $openreqcount = $openreqcount + 1;
            }
        }

        if ($openreqcount > 1) {
            $openreqcount_label = '<span class="count count-label">'.$openreqcount.'</span>';
        }
    }
} else {
    if (! $batch) {
        $afterload = '$("sFirstName").focus();';
    } //Set focus on new request to ID box
    $pagetitle = lg_request_pagetitle;
    if (! $batch) {
        $buttons = '<button tabindex="303" type="submit" name="sub_create" id="sub_create" value="sub_create" class="btn inline-action">'.lg_request_create.'</button>';
        $buttons .= '<button tabindex="304" type="submit" name="sub_create_close" id="sub_create_close" value="sub_create_close" class="btn inline-action" onClick="close_clicked=true;">'.lg_request_create_close.'</button>';
    } else {
        $buttons = '<button tabindex="303" type="button" name="batch" value="batch" class="btn inline-action accent" style="margin-left: 1px;" onclick="if(checkform()){hs_confirm(\''.hs_jshtmlentities(lg_request_batchconfirm).'\',function(){$(\'requestform\').submit();});}">'.lg_request_batch.'</button>';
        $buttons .= '<button tabindex="304" type="button" name="batch_close" value="batch_close" class="btn inline-action accent" onclick="batch_close_clicked=true;close_clicked=true;if(checkform()){hs_confirm(\''.hs_jshtmlentities(lg_request_batchconfirm).'\',function(){$jq(\'#batch_type\').val(\'close\');$(\'requestform\').submit();});}">'.lg_request_batch_close.'</button>';
        $buttons .= '<input type="hidden" name="batch_type" id="batch_type" value="normal" />'; //Hidden field holds type of batch (normal vs close)
    }

    //Public email default
    if (empty($fm['sTitle']) && $batch == false) {
        $fm['sTitle'] = lg_request_subjectdefaultnew;
    }
}

// Get category info
$reqCategory = apiGetCategory($fm['xCategory']);
// Get reporting tags for this request
$fmReportingTags = apiGetRequestRepTags($reqid);
// Get all users, include how many requests are assigned to them
$allStaff = apiGetAssignStaff();

// dynamic form components
$catsList = apiGetAllCategories(0, '');

$statusSelect = '';
$activeStatus = apiGetActiveStatus();

foreach ($activeStatus as $key=>$value) {
    if ($key != hs_setting('cHD_STATUS_SPAM', 2)) {
        $statusSelect .= '<option value="'.$key.'" '.selectionCheck($key, $fm['xStatus']).'>'.$value.'</option>';
    } else { //handle spam status type
        if (! empty($reqid)) {
            $spamreqhis = apiGetInitialRequest($reqid);
            if (! hs_empty($spamreqhis['tEmailHeaders']) || $fm['fOpenedVia'] == 7) {
                $statusSelect .= '<option value="'.$key.'" '.selectionCheck($key, $fm['xStatus']).'>'.$value.'</option>';
            }
        }
    }
}

$contactSelect = '<option value="">'.lg_request_contactedvia.'</option>';
foreach ($GLOBALS['openedVia'] as $key=>$value) {
    if ($key != 6 && $key != 7 && $key != 13 && $key != 14) {
        $contactSelect .= '<option value="'.$key.'" '.selectionCheck($key, $fm['fOpenedVia']).'>'.$value.'</option>';
    }
}

$assignSelect = '<option value="">'.lg_request_assignedto_change.'</option>';
$reportingTagsSelect = '<option value="">&nbsp;</option>';

//render custom fields
$customfieldsdisplay = renderCustomFields($fm, $customFields, $tindex, false, false, '<img src="' . static_url() . '/static/img5/angle-double-right-solid.svg" style="height: 20px;margin-left:3px;margin-right: 3px;" />', true);

//custom JS
$customfieldsjs = '';
if (is_array($customFields) && ! empty($customFields)) {
    foreach ($customFields as $v) {
        //Check for required fields
        if ($v['isRequired'] == 1) {
            //_wrapper check makes sure it's a visible field so the JS doesn't stop hidden custom fields which don't relate to the category
            $customfieldsjs .= '
			if($("Custom'.$v['fieldID'].'") && $("Custom'.$v['fieldID'].'").value == "" && $("xStatus").options[indst].value != '.hs_setting('cHD_STATUS_SPAM', 2).' && $("Custom'.$v['fieldID'].'_wrapper").visible()){
				er += "'.hs_jshtmlentities(lg_request_er_customempty.' '.$v['fieldName']).'\n";
			}
			';
        }

        if ($v['isRequired'] == 1 && $v['fieldType'] == 'checkbox') {
            $customfieldsjs .= '
			if(!$("Custom'.$v['fieldID'].'").checked && $("Custom'.$v['fieldID'].'_wrapper").visible()){
				er += "'.hs_jshtmlentities(lg_request_er_customempty.' '.$v['fieldName']).'\n";
			}
			';
        }

        if ($v['isRequired'] == 1 && $v['fieldType'] == 'drilldown') {
            $customfieldsjs .= '
			if($F("Custom'.$v['fieldID'].'_1") == "" && $("Custom'.$v['fieldID'].'_wrapper").visible()){
				er += "'.hs_jshtmlentities(lg_request_er_customempty.' '.$v['fieldName']).'\n";
			}
			';
        }

        //Check that decimals are decimal. Can be empty or a decimal
        if ($v['fieldType'] == 'decimal') {
            $customfieldsjs .= '
			if($F("Custom'.$v['fieldID'].'") != "" && !/^[0-9\.]+$/.test($F("Custom'.$v['fieldID'].'"))){
				er += "'.hs_jshtmlentities(lg_request_er_decimal.' '.$v['fieldName']).'\n";
			}
			';
        }

        //Check number fields
        if ($v['fieldType'] == 'numtext') {
            $customfieldsjs .= '
			if($F("Custom'.$v['fieldID'].'") != "" && !/^[0-9]+$/.test($F("Custom'.$v['fieldID'].'"))){
				er += "'.hs_jshtmlentities(lg_request_er_number.' '.$v['fieldName']).'\n";
			}
			';
        }

        //Check regex fields
        if ($v['fieldType'] == 'regex') {
            $customfieldsjs .= '
			if($F("Custom'.$v['fieldID'].'") != "" && !'.$v['sRegex'].'.test($F("Custom'.$v['fieldID'].'"))){
				er += "'.hs_jshtmlentities(lg_request_er_regex.' '.$v['fieldName']).'\n";
			}
			';
        }
    }
}

// get mailboxes - used in JS so no line breaks
$mailbox_select = empty($reqid) ? hs_setting('cHD_DEFAULTMAILBOX') : ($fm['xMailboxToSendFrom'] != 0 ? $fm['xMailboxToSendFrom'] : $fm['xOpenedViaId']);
$mailboxesSelect = '<select name="emailfrom" id="emailfrom" style="width:100%;">';
$mailboxesSelect .= '<option value="'.hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILNAME')).'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0" '.selectionCheck(0, $mailbox_select).'>'.hs_jshtmlentities(lg_default_mailbox).' - '.hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILACCT')).'</option>';
$mailboxesres = apiGetAllMailboxes(0, '');
if (is_object($mailboxesres) && $mailboxesres->RecordCount() > 0) {
    while ($box = $mailboxesres->FetchRow()) {
        if (! hs_empty($box['sReplyEmail'])) {
            $mailboxesSelect .= '<option value="'.hs_jshtmlentities($box['sReplyName']).'*'.hs_jshtmlentities($box['sReplyEmail']).'*'.$box['xMailbox'].'" '.selectionCheck($box['xMailbox'], $mailbox_select).'>'.hs_jshtmlentities(replyNameDisplay($box['sReplyName'])).' - '.hs_jshtmlentities($box['sReplyEmail']).'</option>';
        }
    }
}
$mailboxesSelect .= '<option value="" '.selectionCheck(-1, $mailbox_select).'>'.hs_jshtmlentities(lg_dontemail).'</option>';
$mailboxesSelect .= '</select>';

/*****************************************
JAVASCRIPT
*****************************************/

$headscript .= milonic_head();

if ($editor_type == 'wysiwyg') {
    $headscript .= wysiwyg_load('tBody', 'request', $reqid);
} elseif ($editor_type == 'markdown') {
    //Script adds markdown format options and preview to textarea
    $headscript .= markdown_setup('tBody');
} elseif ($editor_type == 'none') {
    //This install is running plain text so setup tbody field to auto expand
    $headscript .= '
		<script language="javascript" type="text/javascript">
		Event.observe(window,"load",function(){
			new ResizeableTextarea($("tBody"));
		});
		</script>';
}

$headscript .= '
	<script type="text/javascript" language="JavaScript">
	//Turn text areas dynamic
	Event.observe(window, \'load\', resize_all_textareas, false);
	</script>

	<script type="text/javascript" language="JavaScript">

	//Change urgent selection
	function urgentChange(){
		if($jq("#fUrgent").val() == 1){ //make not urgent
            $jq.get("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'req_noturgent']).'&rand=" + ajaxRandomString(),{xRequest:'.($reqid ? $reqid : '""').'});
			$jq("#fUrgent").val(0);
			$jq("#make-urgent").removeClass("isurgent");
            $jq("#customer-bar .isurgent").addClass("hidden");
            $jq("#make-urgent span").html("'.hs_jshtmlentities(lg_request_isnoturgent).'");
		}else{
            $jq.get("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'req_isurgent']).'&rand=" + ajaxRandomString(),{xRequest:'.($reqid ? $reqid : '""').'});
			$jq("#fUrgent").val(1);
			$jq("#make-urgent").addClass("isurgent");
            $jq("#customer-bar .isurgent").removeClass("hidden");
            $jq("#make-urgent span").html("'.hs_jshtmlentities(lg_request_isurgent).'");
		}
	}
	</script>

	<script type="text/javascript" language="JavaScript">
		//Set note box editor type
		editor_type = "'.$editor_type.'";

		//Take the request ID search box out of the tab order
		if($("sidebar_reqsearch")) Event.observe(window, "load", function(){$("sidebar_reqsearch").tabIndex=-1}, false);
		';

    if (! $batch and ! $reqid) {
        $headscript .= '
			$jq( document ).ready(function() {
				value = simpleStorage.get("newRequest");
				if (typeof value !== "undefined") {
					set_note_body("tBody", value);
				}
				if (editor_type != "wysiwyg") {
					$jq("#tBody").on("keyup", function(e) {
						simpleStorage.set("newRequest", $jq("#tBody").val());
					});
				}
			});
		';
    }
    if (! $batch && $reqid) {
        $headscript .= '
		//Setup draft saving of notes
		hs_PeriodicalExecuter("saveNoteDraft",saveNoteDraft, '.hs_setting('cHD_SAVE_DRAFTS_EVERY').');

		//Function to save note draft
		latestRequestNote = "";
		function saveNoteDraft(){
			var currentNote = get_note_body("tBody");
			var noteIsNew = false;
			if(latestRequestNote !== currentNote && currentNote !== ""){
				noteIsNew = true;
			}

			if(noteIsNew){
				latestRequestNote = currentNote;
				doNoteDraftSave();
			}
		}

		function doNoteDraftSave(){
			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'save_draft_note']).'";
			var pars = {xRequest:'.$reqid.',xPerson:'.$user['xPerson'].',tBody:get_note_body("tBody"),rand:ajaxRandomString()};
			var call = new Ajax.Request(
				url,
				{
					method: 	"post",
					parameters: pars,
					onSuccess:  function(transport){
									if(transport.responseText.indexOf("<html") == -1){
										$("draft_count").update(transport.responseText);
										$("draft_options_box").update(""); //clear draft options
										$("draft_options_box").hide();
									}
								}
				});
		}

		//Create draft option box
		function draft_options_box(){
			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'get_draft_notes']).'";
			var pars = {xRequest:'.$reqid.',xPerson:'.$user['xPerson'].',rand:ajaxRandomString()};
			var call = new Ajax.Request(
				url,
				{
					method: 	"get",
					parameters: pars,
					onSuccess:  function(transport){
									$("draft_options_box").innerHTML = "";
									$("draft_options_box").insert(transport.responseText);
									hs_overlay("draft_options_box");
								}
				});

			return false;
		}

		function insert_draft_note(){
			//First save whatever is in the note box right now
			//turn this off for now, this needs more research - doNoteDraftSave();

			//Now restore the old draft
			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'get_draft_note']). '";
			var pars = {xDraft:$F("draft_note_select"),rand:ajaxRandomString()};
			var call = new Ajax.Request(
				url,
				{
					method: 	"get",
					parameters: pars,
					onSuccess:  function(transport){
									set_note_body("tBody",transport.responseText);
									Element.scrollTo("sUserId");
								}
				});

			closeAllModals();
		}

		//The initial request history load
		function initRequestHistory(){
			$jq("#request_history_body").load("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'request_history_init', 'xRequest' => $reqid, 'directlink' => $directlink]). '")

			//Set onclick for inline images
			$jq(".note-stream-item-inline-img").live("click",function(){
				var url = $jq(this).prop("src") + "&showfullsize=1";
				var modal = initModal({
					footer: false,
					closeMethods: ["overlay", "button", "escape"],
					html: "<img src=\'" + url + "\' style=\'max-width: 100%;\'>"
				});
			});
		}

		//Change request history view
		function changeRequestHistoryView(){
			var view = $jq("#changeRequestHistoryDropdown").val();
			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'request_history_showall']).'";
			var pars = {fRequestHistoryView:view, xRequest:'.$reqid.',rand:ajaxRandomString()};
			var call = new Ajax.Request(
				url,
				{
					method: 	"get",
					parameters: pars,
					onCreate: function(){
									//Hide it
									$("request_history_body").update(ajaxLoading());
					},
					onSuccess:  function(){
									//update content
									$("request_history_body").update(arguments[0].responseText);
									arguments[0].responseText.evalScripts();

									//make it appear
									$("request_history_body").appear();
								}
				});
		}';
    }

    if ($reqid && ! $batch) {
        $headscript .= '
		//Tell the server the users status has changed. Do on initial load and then every 16 seconds to check
		function person_status_notification(){
			//Check and see if anyone else is in the request
			new Ajax.Request(
			"'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'person_status_requestpage']).'&ppl_list="+($("person_status_user_list") ? $("person_status_user_list").innerHTML : "") + "&rand=" + ajaxRandomString(),
			{
				method: 	"get",
				parameters: {reqid:'.$reqid.'},
				onSuccess:  function(transport){
								if(transport.responseText != ""){
									$("person_status_notification_wrapper").update(transport.responseText);
									$("person_status_notification_wrapper").appear();
								}
							}
			});
		}
		Event.observe(window, "load", function(){setTimeout(function(){ person_status_notification(); }, 2000);});
		hs_PeriodicalExecuter("person_status_notification",person_status_notification, '.hs_setting('cHD_REQ_PAGE_STATUS').');


		function subscription(){
			if($jq("#subscribe_button_text").hasClass("is_subscribed")){
				$jq.get("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'req_unsubscribe']).'&rand=" + ajaxRandomString(),{xRequest:'.($reqid ? $reqid : '""').'});
                $jq("#subscribe_button_text").removeClass("is_subscribed");
                $jq("#subscribe_button_text img").attr("src", "'.static_url().'/static/img5/star-light.svg");
				hs_msg("'.hs_jshtmlentities(lg_request_fb_unsubscribed).'");
			}else{
				$jq.get("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'req_subscribe']).'&rand=" + ajaxRandomString(),{xRequest:'.($reqid ? $reqid : '""').'});
				$jq("#subscribe_button_text").addClass("is_subscribed");
                $jq("#subscribe_button_text img").attr("src", "'.static_url().'/static/img5/star-solid-active.svg");
				hs_msg("'.hs_jshtmlentities(lg_request_fb_subscribed).'");

			}
		}
		';
    }

    if (! $batch) {
        $headscript .= '
		//Setup observation of form to change user status to editing. Wait 5 seconds so all the init stuff of the form has passed since this catches those
		setTimeout(function(){
			//Observer form elements
			new Form.Observer("requestform", 0.8, function(form, value){
				//Update the users status
				person_status_update_details( '.$user['xPerson'].', "request", 2, "'.hs_jshtmlentities(lg_ps_editingrequest).'");

				//Stop observing the form
				this.stop();
			});

			//If using wysiwyg it can not be checked by above so we have to observer on our owned
			if(is_wysiwyg("tBody")){
				new PeriodicalExecuter(function(pe) {
					if( tinyMCE.activeEditor.isDirty() ) {
						person_status_update_details( '.$user['xPerson'].', "request", 2, "'.hs_jshtmlentities(lg_ps_editingrequest).'");
						this.stop();
					}
				}, 2);
			}
		}, 4000);
		';
    }

    $headscript .= '
		//If criteria to send a customer an email is met then highlight in UI
		function subjectChange() {
			$jq("#js-subject").html($jq("#sTitle").val());
		}
		function emailCustomerMsg(){
			var will_email = "'.hs_jshtmlentities(lg_request_emailcustomermsg_active).'";
			var will_not_email = "'.hs_jshtmlentities(lg_request_emailcustomermsg).'";

			//Checks to see if criteria is met to send an email
			if( $F("sEmail") != "" &&
				($("fPublic") && $F("fPublic") == 1) &&
				get_note_body("tBody") != "" &&
				$("emailfrom") && $F("emailfrom") != ""){

					$("email_customer_msg").update(will_email);
					$("email_customer_msg").addClassName("email_customer_msg_active");
			}else{
				if($("fPublic") && $F("fPublic") == 1){
					$("email_customer_msg").update(will_not_email);
					$("email_customer_msg").removeClassName("email_customer_msg_active");
				}
			}
		}
		function emailCustomerMsgBatch() {
			var will_email = "'.hs_jshtmlentities(lg_request_emailcustomermsg_active).'";
			var will_not_email = "'.hs_jshtmlentities(lg_request_emailcustomermsg).'";

			//Checks to see if criteria is met to send an email
			if( ($("fPublic") && $F("fPublic") == 1) &&
				get_note_body("tBody") != ""){

					$("email_customer_msg").update(will_email);
					$("email_customer_msg").addClassName("email_customer_msg_active");
			}else{
				if($("fPublic") && $F("fPublic") == 1){
					$("email_customer_msg").update(will_not_email);
					$("email_customer_msg").removeClassName("email_customer_msg_active");
				}
			}
		}
		'.(! $batch ? 'new PeriodicalExecuter(emailCustomerMsg, 1);' : '').'
		'.($batch ? 'new PeriodicalExecuter(emailCustomerMsgBatch, 1);' : '').'

		// Validate the email address
		function validateRequestEmail(emails){
			var valid = true;
			var msg = "";
			$jq.each(emails.split(","), function(index, value) {
				if ( ! validate_email($jq.trim(value))) {
					valid = false;
					msg += value +" '.lg_request_not_a_valid_email.'\r\n";
				}
			});
			if ( ! valid) {
				hs_alert(msg);
				return false;
			}
			return true;
		}

		//Add an email address to the cc/bcc/to lists
		function add_email(type){
			var hidden = "email"+type+"group";
			var email_group = new Array();
			if($(hidden).value != "") email_group = $(hidden).value.split(",");
			var email_value = $("add"+type+"_email").value
			if ( ! validateRequestEmail(email_value)) {
				return false;
			}
			email_group.push(email_value);
			$(hidden).value = email_group.join(",");

			//Rebuild display list
			build_email_list(type);

			//Empty field
			$("add"+type+"_email").value = "";
		}

		//Remove an email address from the cc/bcc/to lists
		function remove_email(type, email){
			var hidden = "email"+type+"group";
			//Remove from email group
			var email_group = new Array();
			if($(hidden).value != "") email_group = $(hidden).value.split(",");
			var index = hs_indexOf(email_group, email);
			email_group.splice(index,1);
			$(hidden).value = email_group.join(",");

			//Add to inactive list
			var email_group = new Array();
			if($(hidden+"_inactive").value != "") email_group = $(hidden+"_inactive").value.split(",");
			email_group.push(email);
			$(hidden+"_inactive").value = email_group.join(",");

			//Rebuild display list
			build_email_list(type);
		}

		//Add email from inactive list
		function add_email_from_inactive(type,email){
			var hidden = "email"+type+"group_inactive";
			//Add to email field
			$("add"+type+"_email").value = removeSlashes(email);

			//Remove from inactive list
			var email_group = new Array();
			if($(hidden).value != "") email_group = $(hidden).value.split(",");
			var index = hs_indexOf(email_group, email);
			email_group.splice(index,1);
			$(hidden).value = email_group.join(",");

			//Add
			add_email(type);
		}

		//Build the cc/bcc/to lists
		function build_email_list(type){
			var hidden = "email"+type+"group";
			if($(hidden)){
				var list = new Array();

				var email_group = $(hidden).value.split(",");
				for(i=0;i<email_group.length;i++){
					if(email_group[i]) list.push("<span class=\"notification_item notification_item_editable\" onclick=\"remove_email(\'"+type+"\',\'"+addSlashes(email_group[i])+"\');\" style=\"cursor:pointer;\">" + email_group[i] + "</span>");
				}

				//Add emails which have been emailed in the past, but are not currently active in the discussion
				var email_group = $(hidden+"_inactive").value.split(",");
				for(i=0;i<email_group.length;i++){
					if(email_group[i]) list.push("<span class=\"notification_item notification_item_inactive notification_item_editable\" onclick=\"add_email_from_inactive(\'"+type+"\', \'"+addSlashes(email_group[i])+"\');\" style=\"cursor:pointer;\">" + email_group[i] + "</span>");
				}

				$("email"+type+"group_list").innerHTML = list.join(" ");

				//If viewing a request with CC/BCC then automatically unhide that info
				if($F(hidden) != ""){
					add_notification();
				}
			}
		}

		function add_notification(){
			$jq("#public_note_options_table .hidden").css("display", "flex");
			if($jq("#external_note").val() == 1) $jq("#public_note_options_table #hidden_external").show();
		}

		function changeNote(type){
			return changeNoteType(type);
		}

		function changeNoteType(type) {
			$("button-public").removeClassName("btn-selected");
			$("button-private").removeClassName("btn-selected");
			$("button-external").removeClassName("btn-selected");
			$jq("#button-public,#button-private,#button-external,#sub_update,#sub_updatenclose,#sub_create,#sub_create_close").removeClass("btn-request-public").removeClass("btn-request-private").removeClass("btn-request-external");
			$jq("#request-option-wrap").removeClass("request-option-wrap-public").removeClass("request-option-wrap-private").removeClass("request-option-wrap-external");

			no = note_option_string;	//Local note option html

			if(type == "public"){
				$jq("#button-public,#sub_update,#sub_updatenclose,#sub_create,#sub_create_close").addClass("btn-request-public");
				$jq("#request-option-wrap").addClass("request-option-wrap-public");
				$jq("#email_customer_msg").html("'.hs_jshtmlentities(lg_request_emailcustomermsg).'");

				Element.show("note_option_div");	//Show div if it was prev hidden

				//Build form
				var pubform = $("noteoptions_tmpl").innerHTML;

				//Template
				ta = no.replace(/@tabid/g,"");
				ta = ta.replace(/@bodytext/g, pubform);
				ta = ta.replace(/@tabtext/g, "");
				ta = ta.replace(/@tabexp/g, "");

				$("note_option_div").innerHTML = ta;

				//Set hidden fields
				$("fPublic").value = 1;
				$("external_note").value = 0;

				//Build CC/BCC lists
				build_email_list("cc");build_email_list("bcc");

                //On public notes show options right away
                add_notification();

			}else if(type == "private"){
				$jq("#button-private,#sub_update,#sub_updatenclose,#sub_create,#sub_create_close").addClass("btn-request-private");
				$jq("#request-option-wrap").addClass("request-option-wrap-private");
                $jq("#email_customer_msg").html("'.hs_jshtmlentities(lg_request_privdescexp).'");
                $("email_customer_msg").removeClassName("email_customer_msg_active");

				$("note_option_div").innerHTML = "";	//empty

				//Build form, for external notes we do not include cc and bcc
				var privform = $("noteoptions_tmpl").innerHTML;

				//Template
				ta = no.replace(/@tabid/g,"");
				ta = ta.replace(/@bodytext/g, privform);
				ta = ta.replace(/@tabtext/g, "");
				ta = ta.replace(/@tabexp/g, "");

				$("note_option_div").innerHTML = ta;

				//Adjust for private
				$jq("#fPublic").remove();
				$jq("#external_note").remove();
				$jq("#public_note_options_table #hidden-ccs").remove();
                $jq("#public_note_options_table #hidden-bccs").remove();
				$jq("#public_note_options_table #ccstaff_option").show();
				$jq("#public_note_options_table #subscribers_option").show();

			}else if(type == "external"){
				$jq("#button-external,#sub_update,#sub_updatenclose,#sub_create,#sub_create_close").addClass("btn-request-external");
				$jq("#request-option-wrap").addClass("request-option-wrap-external");
                $jq("#email_customer_msg").html("'.hs_jshtmlentities(lg_request_extdescexp).'");
                $("email_customer_msg").removeClassName("email_customer_msg_active");

				$("note_option_div").innerHTML = ""; 	//empty note area
				Element.show("note_option_div");		//Show div if it was prev hidden

				//Build form, for external notes we do not include cc and bcc
				var pubform = $("noteoptions_tmpl").innerHTML;

				//Template
				ta = no.replace(/@tabid/g,"");
				ta = ta.replace(/@bodytext/g, pubform);
				ta = ta.replace(/@tabtext/g, "");
				ta = ta.replace(/@tabexp/g, "");

				$("note_option_div").innerHTML = ta;

				//Set hidden fields
				$("fPublic").value = 0;
				$("external_note").value = 1;

				//On external notes show options right away
				add_notification();

				//Build TO/CC/BCC lists
				build_email_list("cc");build_email_list("bcc");build_email_list("to");

				//Remove any cc/bcc from the internal
				remove_email("cc")
				remove_email("bcc")
				$("emailccgroup").value = ""
				$("emailbccgroup").value = ""

                $jq("#request-drawer").toggle();
			}

		}

		//add another field for file uploads
		var fct = 0; //file upload counter
		var htmlEmails = "'.hs_setting('cHD_HTMLEMAILS').'";
		function addAnotherFile(){
			fct++;
			var no = note_option_string;	//Local note option html

			//Template
			ta = no.replace(/@tabid/g,"file_upload_" + fct);
			ta = ta.replace(/@tabtext/g,"'.hs_jshtmlentities(lg_request_fileuploadtab).' #"+fct);
			ta = ta.replace(/@tabexp/g," <span class=\"cancel\" id=\"cancel"+fct+"\" onClick=\"removeFile(" + fct + ");\" onMouseOver=\"hs_hover(this.id,\'cancel_hover\');\" onMouseOut=\"hs_hover(this.id,\'cancel_hover\');\">'.lg_request_cancel.'</span>");
			ta = ta.replace(/@bodytext/g, "<input type=\"file\" size=\"60\" id=\"attachment_field_"+fct+"\" name=\"doc[]\">");

			new Insertion.Top("attachment_box", ta);

			if (htmlEmails) {
				//Watch for change and insert placeholder if image
				$jq("#attachment_field_"+fct).change(function(e){

					var file = $jq(this).prop(\'files\')
					var mb = '.(hs_setting('cHD_MAIL_MAXATTACHSIZE') / 1000000).';
					if (((file[0].size/1024)/1024).toFixed(4) > mb) {
						msg = "'.lg_request_er_fileupload.'".replace("{filename}", file[0].name).replace("{mb}", mb);
						$jq(this).parent().append("<div class=\'error tddesc\'>"+msg+"</div>");
						$jq(this).val("");
					 	return false;
					}
					insertImagePlaceholder($jq(this).val());
				});
			}
		}

		function insertRealImage(data) {
			if (! data.isImage || editor_type != "wysiwyg") {
				return insertImagePlaceholder(data.sFileName);
			}

			var inline_filename = data.sFileName.split("\\\");
			inline_filename = btoa(inline_filename[(inline_filename.length-1)]);
			var img = "<img src=\'"+data.filePath+"\' />";
			append_wysiwyg_note_body(img);
		}

		// Insert the placeholder into the textarea
		function insertImagePlaceholder(file){
			var ext = file.split(".").pop();
			if ($jq.inArray(ext.toLowerCase(),["png","gif","jpg"]) === -1) {
				return false;
			}

			//Compensate for webkit browsers putting in fake path
			var inline_filename = file.split("\\\");
			inline_filename = inline_filename[(inline_filename.length-1)];

			if(editor_type == "wysiwyg"){
				append_wysiwyg_note_body("<div>##'.hs_jshtmlentities(lg_inline_image).' ("+inline_filename+")##</div>");
			}else{
				insertAtCursor(document.requestform.tBody,"##'.hs_jshtmlentities(lg_inline_image).' ("+inline_filename+")##");
			}
		}

		//remove a file
		function removeFile(id){
			var file = $("file_upload_" + id);
			file.innerHTML = "";
			Element.hide(file);
		}

		var ract = 1; //file upload counter
		function attachDropzoneImage(ra_id, filename, insert) {
			var no = note_option_string;	//Local note option html
			//Remove attachment link
			$jq("#reattach-link-"+ra_id).hide();
			//Template
			ta = no.replace(/@tabid/g,"file_reattach_" + ract);
			ta = ta.replace(/@tabtext/g,"'.hs_jshtmlentities(lg_request_fileuploadtab).'");
			ta = ta.replace(/@tabexp/g," <span class=\"cancel\" id=\"ra-cancel"+ract+"\" onClick=\"removeReAttach(" + ract + ");\" onMouseOver=\"hs_hover(this.id,\'cancel_hover\');\" onMouseOut=\"hs_hover(this.id,\'cancel_hover\');\">'.lg_request_cancel.'</span>");
			ta = ta.replace(/@bodytext/g, "<input type=\"hidden\" name=\"attachment[]\" value=\""+ra_id+"\"> <strong>" + filename + "</strong>");

			if (insert === undefined) {
				insertImagePlaceholder(filename);
			}
			new Insertion.Top("attachment_box", ta);
			ract++;
		}
		//add another re-attachment
		function addAnotherReAttach(ra_id,filename, insert){
			var no = note_option_string;	//Local note option html

			//Remove attachment link
			$jq("#reattach-link-"+ra_id).hide();

			//Template
			ta = no.replace(/@tabid/g,"file_reattach_" + ract);
			ta = ta.replace(/@tabtext/g,"'.hs_jshtmlentities(lg_request_fileuploadtab).'");
			ta = ta.replace(/@tabexp/g," <span class=\"cancel\" id=\"ra-cancel"+ract+"\" onClick=\"removeReAttach(" + ract + ");\" onMouseOver=\"hs_hover(this.id,\'cancel_hover\');\" onMouseOut=\"hs_hover(this.id,\'cancel_hover\');\">'.lg_request_cancel.'</span>");
			ta = ta.replace(/@bodytext/g, "<input type=\"hidden\" name=\"reattach[]\" value=\""+ra_id+"\"> '.lg_request_reattachedfile.': <strong>" + filename + "</strong> ("+ra_id+")");

			if (insert === undefined) {
				insertImagePlaceholder(filename);
			}
			new Insertion.Top("attachment_box", ta);
			ract++;
		}

		//remove a file
		function removeReAttach(id){
			var file = $("file_reattach_" + id);
			file.innerHTML = "";
			Element.hide(file);
		}

		//run a customer history search
		function doHistorySearch(search_type){
			//Make sure iframe is hidden
			$("history_frame_wrapper").hide();

			//Show loading
			$("customer_ajax_history").show();
			$("customer_ajax_history").update(ajaxLoading());

			//Slightly delay ajax call so loading image does not flash awkwardly
			setTimeout(function(){
				//Do search
				var getq = "&search_type=" + search_type + "&sUserId=" + eq($F("sUserId")) + "&sFirstName=" + eq($F("sFirstName")) + "&sLastName=" + eq($F("sLastName")) + "&sEmail=" + eq($F("sEmail")) + "&sPhone=" + eq($F("sPhone"));

				var url = "'.action('Admin\AdminBaseController@adminFileCalled').'";
				var pars = "pg=ajax_gateway&action=historysearch&rand=" + ajaxRandomString() + getq;

				var updateLL = new Ajax.Updater(
							"customer_ajax_history",
							url,
							{method: \'get\', parameters: pars, onFailure: ajaxError,evalScripts: false});
			},300);
		}

		//Show a request inline
		function showRequestDetails(reqid){
			//Hide search box
			$("customer_ajax_history").hide();

			//Set iframe src and show
			frames["history_frame"].location.href = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request.static', 'from_history_search' => 1]). '&reqid=" + reqid + "";
			$("history_frame_wrapper").show();
		}

		//Return to search results
		function returnToSearchResults(){
			$("history_frame_wrapper").hide();
			$("customer_ajax_history").show();
		}

		// Open reminders modal
        function openReminders() {
            hs_overlay({
                href:\'admin?pg=ajax_gateway&action=remindershow&reqid='.$reqid.'&rand=\'+ajaxRandomString(),
            });
		}

        // Open addressbook modal
        function openAddressBook() {
            hs_overlay({
                href:"admin?pg=ajax_gateway&action=addressbook",
                title:"'.hs_jshtmlentities(lg_addressbook_title). '",
                beforeOpen: function() {
                    addressBookInternal();
                },
                onOpen: function() {
					ab_valid = new Validation("ab_addcontact_form", {onSubmit:false, useTitles:true});
					new Control.Tabs("addressbook_tabs");
                }
            });
        }

		//Live lookup search from the address book
		function addressBookLiveLookup(){
			$jq.post("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'addressbook_livelookup']).'&rand=" + ajaxRandomString(),
					  $jq("#ab_livelookup_form").serialize(), function(data){
					  		$jq("#ab_contact_list").html(data);
					  		addressBookSetupLinks();
					  });

			//Change header
			$("ab_contact_header").update("'.lg_addressbook_llheader.'");
		}

		//Scroll div to letter
		function addressBookScroll(letter){
			//Find letter position
			var new_pos = $("ab-list-header-" + letter).positionedOffset().top;

			if(new_pos < 0){
				new_pos = 0;
			}

			$("ab_contact_list").scrollTop = new_pos;
		}

		//Internal address book list
		function addressBookInternal(){
			new Ajax.Updater("ab_contact_list",
							 "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'addressbook_internal']).'&rand=" + ajaxRandomString(),
							 {method: "get",
							  onComplete: function(s){
							  		addressBookSetupLinks();
							  	}
							  });
		}

		//Dectivate letters and TO/CC/BCC links
		function addressBookSetupLinks(){
			//go over all letters and see if there is a header for them
			$$(".ab-list-letter-link").each(function(s){
				//First remove the class in case this has been run before
				$(s).removeClassName("ab-list-letter-link-inactive");

				//Add back in if now necessary
				if(!$("ab-list-header-"+trim(s.innerHTML))) $(s).addClassName("ab-list-letter-link-inactive");

				//Show TOs if an external note
				if($("external_note") && $F("external_note") == 1) $$(".ab-link-to").each(function(s){s.show();})
			});

			//Check TO/CC/BCC
			var ab_check_to = ($("emailtogroup") ? $F("emailtogroup").split(",") : "");
			var ab_check_cc = ($("emailccgroup") ? $F("emailccgroup").split(",") : "");
			var ab_check_bcc = ($("emailbccgroup") ? $F("emailbccgroup").split(",") : "");

			$$(".ab-list-email").each(function(s){
				var info = s.innerHTML.split("###");

				if(ab_check_to.indexOf(info[0]) != -1){ addressBookDeActivateLink(info[1], "to"); }
				if(ab_check_cc.indexOf(info[0]) != -1){ addressBookDeActivateLink(info[1], "cc"); }
				if(ab_check_bcc.indexOf(info[0]) != -1){ addressBookDeActivateLink(info[1], "bcc"); }
			});
		}

		//Deactivate a TO/CC/BCC link
		function addressBookDeActivateLink(id,type){
			var elem = "ab-link-"+type+"-"+id;
			$(elem).addClassName("ab-list-cclink-inactive");
			$(elem).observe("click", function(event){ Event.stop(event); });
		}

		//Delete contact
		function addressBookDeleteContact(id){
			new Ajax.Updater("ab_contact_list",
							 "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'addressbook_deletecontact']).'&rand=" + ajaxRandomString(),
							 {method: "post", parameters: "xContact="+id});
		}

		//Add a contact
		function addressBookAddContact(){
			var ac = new Ajax.Request(
			"'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'addressbook_addcontact']).'&rand=" + ajaxRandomString(),
			{
				method: "POST",
				parameters: Form.serialize("ab_addcontact_form"),
				onComplete: function(s){
				    // Reset the form and the refetch the list.
					$("ab_addcontact_form").reset();
					addressBookInternal();
				}
			});
		}

		//run a live lookup
		function doLiveLookup(source_id){
            $jq("#livelookup_tab").show();

			//Loading
			$("customer_ajax_ll").update(ajaxLoading());

			//Do lookup
			var getq;
			var source_id = source_id;
			var custid = $("sUserId").value;
			var fname  = $("sFirstName").value;
			var lname  = $("sLastName").value;
			var email  = $("sEmail").value;
			var phone  = $("sPhone").value;
			var all_fields = getRequestFields();

			var reqid  = '.($reqid ? $reqid : '""').';

			getq = "&source_id="+source_id+"&customer_id=" + eq(custid) + "&first_name=" + eq(fname) + "&last_name=" + eq(lname) + "&email=" + eq(email) + "&phone=" + eq(phone) + "&request=" + reqid;
			getq = getq + "&" + all_fields.toQueryString();

			var url = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'livelookup']).'&rand=" + ajaxRandomString();
			var pars = getq;

			var updateLL = new Ajax.Updater(
						"customer_ajax_ll",
						url,
						{method: \'post\', parameters: pars, onFailure: ajaxError,evalScripts: false});

		}';

        if (hs_setting('cHD_LIVELOOKUP') == 1 && ! hs_empty(hs_setting('cHD_LIVELOOKUPAUTO')) && $reqid != '') {
            $headscript .= '
			//Auto run live lookup
			function autoLiveLookup(){
				//IE needs a little time
				setTimeout(function(){
				    if ($("'. hs_setting('cHD_LIVELOOKUPAUTO') .'").value == "") {
				        Control.Tabs.findByTabId("customer_tab").setActiveTab("livelookup_tab");
				    }
				},20);
			}
			Event.observe(window, "load", autoLiveLookup);';
        }

        $headscript .= '
		//send out insert JS
		function insertCusData(data){
			var fields = new Array();

			//Decode
			for(key in data){
				fields[key] = decodeURIComponent(data[key]);
			}

            if(fields["first_name"]){
                $("sFirstName").value = fields["first_name"].replace(/^\s*|\s*$/g,"");
                $jq("#customer_main_first").html(fields["first_name"].replace(/^\s*|\s*$/g,""));
            }
            if(fields["last_name"]){
                $("sLastName").value = fields["last_name"].replace(/^\s*|\s*$/g,"");
                $jq("#customer_main_last").html(fields["last_name"].replace(/^\s*|\s*$/g,""));
            }
			if(fields["customer_id"]){
                $("sUserId").value = fields["customer_id"].replace(/^\s*|\s*$/g,"");
                $jq("#customer_main_userid").html(fields["customer_id"].replace(/^\s*|\s*$/g,""));
            }
			if(fields["email"]){
                $("sEmail").value = fields["email"].replace(/^\s*|\s*$/g,"");
                $jq("#customer_main_email").html(fields["email"].replace(/^\s*|\s*$/g,""));
            }
			if(fields["phone"]){
                $("sPhone").value = fields["phone"].replace(/^\s*|\s*$/g,"");
                $jq("#customer_main_phone").html(fields["phone"].replace(/^\s*|\s*$/g,""));
            }

			for(key in fields){
				if(key.substr(0,6) == "Custom" && fields[key] != ""){
					elem = $(key);
					if($(key)){
						if(elem.type.toLowerCase() == "checkbox"){
							// If the value is a string 0 then set it as unchecked.
							if (fields[key] === "0") {
								elem.checked = false;
							} else {
								elem.checked = true;
							}
						}else if(elem.tagName.toLowerCase() == "input" || elem.tagName.toLowerCase() == "textarea"){
							elem.value = fields[key].replace(/^\s*|\s*$/g,"");
						}else if(elem.tagName.toLowerCase() == "select"){
							setSelectToValue(key,fields[key].replace(/^\s*|\s*$/g,""));
							if($(key).onchange) $(key).onchange();
						}
					}
				}
			}

			$("customer_ajax_ll").innerHTML = "";
			closeAllModals();
            $jq(window).scrollTop(0);
            //Set tab back to customer so they can see the inserted info
            Control.Tabs.findByTabId("customer_tab").setActiveTab("customer_tab");
		}

		function insertCusDataFromHisSearch(){

			$("sUserId").value 		= window.frames["history_frame"].document.forms["requestform"].elements["sUserId"].value.replace(/^\s*|\s*$/g,"");
			$("sFirstName").value 	= window.frames["history_frame"].document.forms["requestform"].elements["sFirstName"].value.replace(/^\s*|\s*$/g,"");
			$("sLastName").value 	= window.frames["history_frame"].document.forms["requestform"].elements["sLastName"].value.replace(/^\s*|\s*$/g,"");
			$("sEmail").value 		= window.frames["history_frame"].document.forms["requestform"].elements["sEmail"].value.replace(/^\s*|\s*$/g,"");
			$("sPhone").value 		= window.frames["history_frame"].document.forms["requestform"].elements["sPhone"].value.replace(/^\s*|\s*$/g,"");

            $jq("#customer_main_first").html(window.frames["history_frame"].document.forms["requestform"].elements["sFirstName"].value.replace(/^\s*|\s*$/g,""));
            $jq("#customer_main_last").html(window.frames["history_frame"].document.forms["requestform"].elements["sLastName"].value.replace(/^\s*|\s*$/g,""));
            $jq("#customer_main_userid").html(window.frames["history_frame"].document.forms["requestform"].elements["sUserId"].value.replace(/^\s*|\s*$/g,""));
            $jq("#customer_main_email").html(window.frames["history_frame"].document.forms["requestform"].elements["sEmail"].value.replace(/^\s*|\s*$/g,""));
            $jq("#customer_main_phone").html(window.frames["history_frame"].document.forms["requestform"].elements["sPhone"].value.replace(/^\s*|\s*$/g,""));

			$("customer_ajax_history").update("");
            $jq(window).scrollTop(0);

            //Set tab back to customer so they can see the inserted info
            Control.Tabs.findByTabId("customer_tab").setActiveTab("customer_tab");
		}

		close_clicked = false;
		batch_close_clicked = false;
		continue_form_check = true;
		function checkform(){
			disableSubmit(); // disable the submit button to prevent double submissions
			simpleStorage.deleteKey("newRequest");

			//First do check to see if we are still logged in, if not show login box
			new Ajax.Request("'.action('Admin\AdminBaseController@sessionCheck').'",{asynchronous:false,
												 onFailure:function(){
													continue_form_check = false;
												 },
												 onSuccess:function(){
												 	continue_form_check = true;
												 }});

			if(continue_form_check){
				var er = "";
				var ind = document.getElementById("xCategory").selectedIndex;
				var indst = document.getElementById("xStatus").selectedIndex;

				if($("sUserId")){ //will not be set when in batch mode
					if(get_note_body("tBody") == "" && "'.$reqid.'" == ""){
						er += "'.hs_jshtmlentities(lg_request_er_nonotecreate).'\n";
					}
				}

                if(document.getElementById("xCategory").options[ind].value == 0 && document.getElementById("xStatus").options[indst].value != '.hs_setting('cHD_STATUS_SPAM', 2).'){
					er += "'.hs_jshtmlentities(lg_request_er_nocategory).'\n";
                }

				if($("sUserId")){ //will not be set when in batch mode
					if(document.getElementById("sUserId").value == "" && document.getElementById("sFirstName").value == "" &&
						document.getElementById("sLastName").value == "" && document.getElementById("sEmail").value == "" &&
						document.getElementById("sPhone").value == "" && document.getElementById("xStatus").options[indst].value != '.hs_setting('cHD_STATUS_SPAM', 2).'){
						er += "'.hs_jshtmlentities(lg_request_er_nocustinfo).'\n";
					}
				}

				if(document.getElementById("fOpenedVia")){
					ovind = document.getElementById("fOpenedVia").selectedIndex;
					if(document.getElementById("fOpenedVia").options[ovind].value == ""){
						er += "'.hs_jshtmlentities(lg_request_er_nocontactvia).'\n";
					}
				}

				//External notes must have a TO
				if($("external_note") && $F("external_note") == 1){
					if($F("emailtogroup") == ""){
						er += "'.hs_jshtmlentities(lg_request_er_noexternalto).'\n";
					}
				}

				if($("sUserId")){ //will not be set when in batch mode
				'.$customfieldsjs.'
				}

				//Attachment check
				if($jq("[name=\'doc[]\'],[name=\'reattach[]\']").length > 0){
					if(get_note_body("tBody") == ""){
						er += "'.hs_jshtmlentities(lg_request_er_nonoteattach).'\n";
					}
				}

				if(close_clicked){
					var ind = document.getElementById("xStatus").selectedIndex;
					if(document.getElementById("xStatus").options[ind].value == "'.hs_setting('cHD_STATUS_ACTIVE').'"){
						if(batch_close_clicked){
							er += "'.hs_jshtmlentities(lg_request_er_closewhileactive).'\n";
						}else if(er == ""){ //only for normal close, not batch closes

							//Different tooltips for update and close vs create and close
							if(document.getElementById("sub_create_close")){
								new Tip("sub_create_close", \'<div style=""><select id="close_status_select" onchange="setTimeout(function(){$(\\\'sub_create_close\\\').prototip.hide();},200);$(\\\'xStatus\\\').setValue($F(this.id));$(\\\'sub_create_close\\\').click();"></select></div>\', {
										title: "",
										border: 0,
										radius: 0,
                                        className: "hstinytipfat",
                                        stem: "bottomMiddle",
										hideOn: { element: "closeButton", event: "click" },
										showOn: "click",
										hideOthers: true,
										width: "250px",
										hook: { target: "topMiddle", tip: "bottomMiddle" }
									});

								$("sub_create_close").prototip.show();
							}else{
								new Tip("sub_updatenclose", \'<div style=""><select id="close_status_select" onchange="setTimeout(function(){$(\\\'sub_updatenclose\\\').prototip.hide();},200);$(\\\'xStatus\\\').setValue($F(this.id));$(\\\'sub_updatenclose\\\').click();"></select></div>\', {
										title: "",
										border: 0,
										radius: 0,
                                        className: "hstinytipfat",
                                        stem: "bottomMiddle",
										hideOn: { element: "closeButton", event: "click" },
										showOn: "click",
										hideOthers: true,
										width: "250px",
										hook: { target: "topMiddle", tip: "bottomMiddle" }
									});

								$("sub_updatenclose").prototip.show();
							}

							//Setup select
							$("close_status_select").update($("xStatus").innerHTML);
							$("close_status_select").options[0].text="'.hs_jshtmlentities(lg_request_er_choosestatus).'";
							$("close_status_select").options[0].value="";
							$("close_status_select").selectedIndex = 0;

							//set back to false, for next click
							close_clicked = false;

							//end check
                            enableSubmit();
							return false;
						}
					}
				}

				//set back to false, for next click
				close_clicked = false;

				if(er.length != 0){
					hs_alert(er);
                    enableSubmit();
					return false;
				}

				'.($reqid ? '
				//Check if timer running, if so then submit time
				if(hs_isdefined("timerMin") && $jq("#newtTime").val() != ""){
					logTime();
				}' : '').'

				return true;
			}else{
				return false;
			}
		}

        function disableSubmit() {
            $jq("#sub_update, #sub_updatenclose, #sub_create, #sub_create_close").click(function(e){
                return false;
            }).css("opacity", ".5");
        }

        function enableSubmit() {
            $jq("#sub_update, #sub_updatenclose, #sub_create, #sub_create_close").off().css("opacity", "1");
        }

		function merge_request_id(){
			hs_overlay({href:"'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'mergeid']).'",width:300,onComplete:function(){ $jq("#merge_req_id").focus(); }});

			return false;
		}

		function merge_request(reqid){
			//Do not allow merge to self
			if(reqid != "'.$reqid.'"){
				$jq("#cboxLoadedContent").html("");
				hs_overlay({width: "500px", href:"'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'mergeconfirm', 'req_from' => $reqid]).'&req_into="+reqid, title:\''.hs_jshtmlentities(lg_request_mergeconfirm).'\'});
			}else{
				hs_alert("'.hs_jshtmlentities(lg_request_mergetoself).'");
			}
		}

		function merge_perform(reqfrom,reqinto){
			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'do_merge']).'&req_from="+ reqfrom +"&req_into=" + reqinto;

			$jq.ajax({
				url: url,
				success: function(){
					goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request']).'&reqid=" + reqinto);
				},
				error: function(){
					hs_alert("'.hs_jshtmlentities(lg_request_mergefailed).'");
				}
			});
		}

		function do_push(){

			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'push_request']).'";
			var pars = "reqid=" + $F("reqid") + "&tComment=" + eq($F("tComment")) + "&push_option=" + eq($F("push_option")) + "&rand=" + ajaxRandomString();

			//Once we have the reqid value show the loading text
			var original_content = $jq("#pushes_list").html();
			$jq("#pushes_list").html(ajaxLoadingImg());

			$jq.ajax({
				type: "POST",
				url: url,
				data: pars,
				success: function(data){
					$jq("#pushes_list").html(data);
					hs_msg("'.hs_jshtmlentities(lg_request_reqpushok).'");
				},
				error: function(data){
					hs_alert(data.responseText);
					$jq("#pushes_list").html(original_content);
				}
			});

			//Empty comment when complete
			$jq("tComment").html("");

			return false;
		}

		//Retrieve response in JSON format
		var responseRun = false;
		function getResponse(id, totalResponses){
			//Prevent multiple calls due to double click
			if(!responseRun){

				//Get response
				var url  = "'.action('Admin\AdminBaseController@adminFileCalled').'";
				var pars = "pg=ajax_gateway&action=response&id=" + id + "&xRequest='.$reqid.'&editor_type=" + editor_type + "&totalResponses=" + totalResponses + "&rand=" + ajaxRandomString();

				var call = new Ajax.Request(
					url,
					{
						method: 	 "get",
						parameters:  pars,
						onComplete:  function(){ appendResponse(eval( "(" + arguments[0].responseText + ")" )); }
					});

				//setup reset of response run
				responseRun = true;
				setTimeout(function(){ responseRun=false; }, 500);

			}
		}

		//Needed to keep loop from overtaking itself and crashing the browser
		function addTOfromResponse(email){
			$("addto_email").value = trim(email);
			add_email("to");
		}

		//Needed to keep loop from overtaking itself and crashing the browser
		function addCCfromResponse(email){
			$("addcc_email").value = trim(email);
			add_email("cc");
		}

		//Needed to keep loop from overtaking itself and crashing the browser
		function addBCCfromResponse(email){
			$("addbcc_email").value = trim(email);
			add_email("bcc");
		}

		function appendResponse(resp){
			//Set arrays of custom field types for use below, especially for dates
			var custom_fields = new Array();
			';
            if (is_array($customFields) && ! empty($customFields)) {
                foreach ($customFields as $k=>$v) {
                    $headscript .= 'custom_fields["Custom'.$v['fieldID'].'"] = "'.$v['fieldType'].'"'."\n";
                }
            }
			$headscript .= '
			if(editor_type == "wysiwyg"){
				append_wysiwyg_note_body(resp.text);
			} else if(resp.text && editor_type != "wysiwyg"){
				insertAtCursor(document.requestform.tBody,resp.text+"\n");
			}

			//Handle advanced options
			if(resp.options){
				eval("options = " + resp.options);

				if(resp.documents){
					resp.documents.forEach(function(item){
						file = $jq.parseJSON(item);
						addAnotherReAttach(file.xDocumentId, file.sFilename);
					});
				}

				//Needs to be last or tBody insert breaks in IE
				if(options["fPublic"] && options["fPublic"] == 1) changeNote("public");
				if(options["fPublic"] && options["fPublic"] == 2) changeNote("private");
				if(options["fPublic"] && options["fPublic"] == 3) changeNote("external");

				if(options["xStatus"] && options["xStatus"] != "") setSelectToValue("xStatus",options["xStatus"]);
				if($("xCategory").value != options["xCategory"] && options["xCategory"] != "" && options["xCategory"] != 0){
					setSelectToValue("xCategory",options["xCategory"]);
					categorySet();	//Must call categorySet after change
				}
				//Set assigned to
				if($("xPersonAssignedTo").value != options["xPersonAssignedTo"] && options["xPersonAssignedTo"] != ""){
					setSelectToValue("xPersonAssignedTo_select",options["xPersonAssignedTo"]);
					changeAssignment($F("xCategory"),options["xPersonAssignedTo"]);
				}

				if($("sTitle") && options["sTitle"] != "") $("sTitle").value = options["sTitle"];
				subjectChange();

				if($("emailfrom") && options["emailfrom"] != ""){
					if(options["emailfrom"] == "dontemail"){
						//Do not email is always last
						$("emailfrom").selectedIndex = $("emailfrom").options.length - 1;
					}else{
						setSelectToValue("emailfrom", options["emailfrom"]);
					}
				}

				if($("emailtogroup") && options["togroup"] && options["togroup"] != ""){
					var emails = options["togroup"].split(",");
					for(i=0;i < emails.length;i++){
						setTimeout("addTOfromResponse(\""+emails[i]+"\")", 500);
					}
				}

				if($("emailccgroup") && options["ccgroup"] && options["ccgroup"] != ""){
					var emails = options["ccgroup"].split(",");
					for(i=0;i < emails.length;i++){
						setTimeout("addCCfromResponse(\""+emails[i]+"\")", 500);
					}
				}

				if($("emailbccgroup") && options["bccgroup"] && options["bccgroup"] != ""){
					var emails = options["bccgroup"].split(",");
					for(i=0;i < emails.length;i++){
						setTimeout("addBCCfromResponse(\""+emails[i]+"\")", 500);
					}
				}

				if(options["xReportingTags"] && options["xReportingTags"] != ""){
					for(i=0;i < options["xReportingTags"].length;i++){
						var rid = options["xReportingTags"][i];
						if($("reporting-tag-"+rid) && ! $("reporting-tag-"+rid).hasClassName("active")){
							repTagChecked(rid);
						}
					}
				}

				for(key in options){
					if(key.indexOf("_") == -1){
						if(key.substr(0,6) == "Custom" && options[key] != "" && $(key) && $(key + "_wrapper").visible()){
							elem = $(key);

							if(elem.type.toLowerCase() == "checkbox"){
								elem.checked = true;
							}else if(elem.tagName.toLowerCase() == "input" || elem.tagName.toLowerCase() == "textarea"){
								elem.value = options[key];
								//If a date also show the date in the display box
								if($("calendar_date_"+key)){
									var format = custom_fields[key] == "datetime" ? "'.hs_setting('cHD_POPUPCALDATEFORMAT').'" : "'.hs_setting('cHD_POPUPCALSHORTDATEFORMAT').'";
									var d = new Date(options[key] * 1000);
									$("calendar_date_"+key).update(d.print(format));
								}
							}else if(elem.tagName.toLowerCase() == "select"){
								setSelectToValue(key,options[key]);
								if($(key).onchange) $(key).onchange();
							}
						}
					}else{
						tempname = key.replace(/_[0-9]+/g,"");
						//Special section to handle drill down fields
						if(key.substr(0,6) == "Custom" && options[key] != "" && $(tempname + "_wrapper").visible()){
							setSelectToValue(key,options[key]);
							if($(key).onchange) $(key).onchange();
						}
					}
				}
			}

			return false;
		}

		function hs_forward(reqhisid, into){
			//Quote note
			hs_quote(reqhisid,into);

			//Find every doc in this history item and reattach it
			$$("#xRequestHistory-"+reqhisid+" .reattach-link").each(function(s){s.onclick();});

			//Get the email subject and insert it
			//Prevent subject line reset for now as can cause confusion
			//$$("#xRequestHistory-"+reqhisid+" .email-subject-line").each(function(s){ if(s.innerHTML != ""){ $("sTitle").value = "'.lg_request_fwd.': " + s.innerHTML.replace(/\{.*\}/g, "") } });

			//Hide tip
			Tips.hideAll();
		}

		function hs_quote(reqhisid,into){
			Element.scrollTo("requestform");
			//Field.focus("tBody"); //breaks in IE7

			var url  = "'.action('Admin\AdminBaseController@adminFileCalled').'";
			var pars = "pg=ajax_gateway&action=quote&editor=" + editor_type + "&reqhisid=" + reqhisid + "&reqid=" + '.$reqid.'  + "&rand=" + ajaxRandomString();

			var call = new Ajax.Request(
				url,
				{
					method: 	 "get",
					parameters:  pars,
					onComplete:  function(){
						if(editor_type == "wysiwyg"){
                            append_wysiwyg_note_body(arguments[0].responseText);
						}else if(editor_type == "markdown"){
							insertAtCursor(document.requestform.tBody, arguments[0].responseText);
						}else{
							insertAtCursor(document.requestform.tBody, arguments[0].responseText);
						}
					 }
				});

			//Hide tip
			Tips.hideAll();
		}

		function hs_quote_public(reqid,into){
			Element.scrollTo("requestform");
			//Field.focus("tBody"); //breaks in IE7

			var url  = "'.action('Admin\AdminBaseController@adminFileCalled').'";
			var pars = "pg=ajax_gateway&action=quote&allpublic=1&editor=" + editor_type + "&reqid=" + reqid + "&rand=" + ajaxRandomString();

			var call = new Ajax.Request(
				url,
				{
					method: 	 "get",
					parameters:  pars,
					onComplete:  function(){
						if(editor_type == "wysiwyg"){
                            append_wysiwyg_note_body(arguments[0].responseText);
						}else if(editor_type == "markdown"){
							insertAtCursor(document.requestform.tBody, arguments[0].responseText);
						}else{
							insertAtCursor(document.requestform.tBody, arguments[0].responseText);
						}
					 }
				});
		}

		function submit_reminder(){
			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'reminderset']). '&rand=" + ajaxRandomString();

			$jq.post(url, $jq("#reminderpopupform").serialize());
			closeAllModals();
			hs_msg("Success");
		}
		';

    if (hs_setting('cHD_TIMETRACKER')) {
        $headscript .= '
		//Timer Vars
		var timerRunning = false;
		var timerStarted = false;
		var timerHour	= 00;
		var timerMin	= 00;
		var timerSec	= 00;

		//new PeriodicalExecuter(updateClock,1);

		function toggleTimer(){
			if(timerRunning){
				timerRunning 	  = false;
				$jq("#newtTime").timer("pause");
				$jq("#timerimg").html("'.hs_jshtmlentities(lg_request_starttimer).'");
			}else{
				timerRunning 	  = true;
				if (timerStarted) {
				    $jq("#newtTime").timer("resume");
				} else {
                    $jq("#newtTime").timer({
                        editable: false,
                        format: "%H:%M:%S"
                    });
                    timerStarted = true;
        		}
				$jq("#timerimg").html("'.hs_jshtmlentities(lg_request_stoptimer).'");
			}
		}

		function updateClock(){
			if(timerRunning){
				timerSec++;	//update total second count
var now = new Date();
console.log(timerSec, now.getMinutes() +\':\'+ now.getSeconds());
				if(timerSec==60){
					timerSec = 0;
					timerMin++;
					if(timerMin==60){
						timerMin = 0;
						timerHour++;
					}
				}

				$("newtTime").value		= hs_pad(timerHour.toString(),"0",2,true) + ":" + hs_pad(timerMin.toString(),"0",2,true) + ":" + hs_pad(timerSec.toString(),"0",2,true);
				//$("seconds").innerHTML 	= ":" + hs_pad(timerSec.toString(),"0",2,true);
			}
		}

		function logTime(){

			var url  = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'addtime']).'";
			var pars = Form.serialize("requestform") + "&xRequest='.$reqid.'" + "&rand=" + ajaxRandomString();

			var call = new Ajax.Request(
				url,
				{
					method: 	 "post",
					asynchronous: false, // this keeps the request form from submitting before the ajax req is complete. Important when being called from checkform()
					parameters:  pars,
					onComplete:  displayTimeResult
				});

			//Stop timer if running and reset
			if(timerRunning){
				toggleTimer();
			}

			//Reset
			$jq("#newtTime").val("");
			$jq("#newtTime").timer("reset");
			$("seconds").innerHTML = "";
		}

		function deleteTime(timeid){
			hs_confirm("'.hs_jshtmlentities(lg_request_timedelcheck).'",function(){deleteTimeAction(timeid);});
		}

		function deleteTimeAction(timeid){

			var url  = "'.action('Admin\AdminBaseController@adminFileCalled').'";
			var pars = "pg=ajax_gateway&action=deletetime&xTimeId=" + timeid + "&xRequest='.$reqid.'" + "&rand=" + ajaxRandomString();

			var call = new Ajax.Request(
				url,
				{
					method: 	 "get",
					parameters:  pars,
					onComplete:  displayTimeResult
				});
		}

		function displayTimeResult(result){
			eval("response = " + result.responseText);

			if(response["error"]){
				hs_msg(response["error"],true);
			}else{
				//insert new table rows
				$("time_body").innerHTML = response["html"];
				$("time-tracker-total").innerHTML = response["time"];
				$("tDescription").value = "";
				$("newtTime").value = "";
				$jq("#fBillable").removeAttr("checked");
				hs_msg(response["msg"]);
			}
		}

		';
    }

    //output cat list with people and tags
    $headscript .= '
		people = new Array();
		reptags = new Array();
	';
    $catsList->Move(0);
    while ($c = $catsList->FetchRow()) {
        $i = 0;
        $out = '';
        $jspeople = hs_unserialize($c['sPersonList']);
        if (is_array($jspeople)) {
            $jsarrsize = count($jspeople);
            $inbox_default = $c['xPersonDefault'] == 0 ? ' ('.lg_default.')' : '';
            $out .= '["0", "'.utf8_strtoupper(lg_inbox).$inbox_default.'","<img src=\"'.static_url().'/static/img5/inbox-in-solid.svg\" style=\"width:48px;height:48px;\" />","'.utf8_strtoupper(lg_inbox).'"],'; // add the inbox as an option
            foreach ($jspeople as $p) {
                $i++;
                //if the user hasn't been deleted since the user list for the cat was created
                if (isset($allStaff[$p]) && $allStaff[$p]['fDeleted'] == 0) {
                    //user name/assignments
                    $out = $out.'["'.$p.'","'.hs_jshtmlentities($allStaff[$p]['namereq']);
                    //if user is default for cat then show
                    if ($c['xPersonDefault'] == $p) {
                        $out = $out.' ('.lg_default.') ';
                    }
                    //if user is out then show
                    if ($allStaff[$p]['xPersonOutOfOffice'] != 0) {
                        $out = $out.' ('.lg_out.') ';
                    }

                    $avatar = new HS\Avatar\Avatar();
                    $at_photo = $avatar->xPerson($p)->html();

                    $at_name = $allStaff[$p]['sFname'].' '.$allStaff[$p]['sLname'];
                    $out = $out.'","'.hs_jshtmlentities($at_photo).'","'.hs_jshtmlentities($at_name).'"]';

                    if ($jsarrsize != $i) {
                        $out = $out.',';
                    }
                }
            }
            $headscript .= 'people['.$c['xCategory'].'] = [ '.$out.' ];'."\n\n";
        }

        $i = 0;
        $out = '';
        $jstags = apiGetReportingTags($c['xCategory']);
        if (is_array($jstags)) {
            $jsarrsize = count($jstags);
            foreach ($jstags as $rk=>$p) {
                $out = $out.'["'.$rk.'","'.hs_jshtmlentities(rawurldecode($p)).'"]';
                $i++;
                if ($jsarrsize != $i) {
                    $out = $out.',';
                }
            }
            $headscript .= 'reptags['.$c['xCategory'].'] = new Array('.$out.');'."\n\n";
        } else {
            $headscript .= 'reptags['.$c['xCategory'].'] = new Array();'."\n\n";
        }
    }

$headscript .= '
        //$jq("body").prepend("<div class=\'cover\'></div>");
		$jq().ready(function(){
			var myDropzone = new Dropzone("div#attach_files", {
			    paramName: "doc",
				url: "'.cHOST. '/admin?pg=ajax_gateway&action=dragdrop",
			});
			myDropzone.on("addedfile", function(file) {
				$jq("#attach_files").removeClass("show-drag-zone");
			});

			myDropzone.on("complete", function(file) {
			    data = $jq.parseJSON(file.xhr.responseText);
                if (! data.isImage) {
                    attachDropzoneImage(data.id, data.sFileName, false);
                } else {
                    insertRealImage(data);
                }
				$jq(".dz-preview").remove();
			});

			$jq("body").bind("dragenter", function(){
				$jq("#attach_files").addClass("show-drag-zone");
			});

			var lastTarget = null;
			function isFile(evt) {
				var dt = evt.dataTransfer;

				for (var i = 0; i < dt.types.length; i++) {
					if (dt.types[i] === "Files") {
						return true;
					}
				}
				return false;
			}
			myDropzone.on("dragenter", function(file) {
				lastTarget = file.target;
			});
			myDropzone.on("dragleave", function(file) {
				file.preventDefault();
			});
			myDropzone.on("dragover", function(e) {
				e.preventDefault();
			});
			myDropzone.on("drop", function(e) {
				e.preventDefault();
			});
		});

		function categorySet(issetup){
			var origcat = "'.$fm['xCategory'].'";
			var origassignto = "'.$fm['xPersonAssignedTo'].'";
			var origreptags = new Array();
			var issetup = (typeof issetup == "undefined" ? false : true);
			origreptags.inArray = hs_inArray;	//add prototype'."\n";
            if (isset($fmReportingTags)) {
                $rtList = $fmReportingTags;
                if (is_array($rtList)) {
                    foreach ($rtList as $k=>$v) {
                        $headscript .= 'origreptags.push("'.$k.'");';
                    }
                }
            }

$headscript .= '// new cat id
				var newcatind = document.getElementById("xCategory").selectedIndex;
				var newcat	  = document.getElementById("xCategory").options[newcatind].value;

				// If no new cat then hide other fields
				if(newcat == "" || newcat == 0){
					$("category-fields-wrap").hide();
				}

				// reset assign to
				document.getElementById("xPersonAssignedTo_select").options.length = 0;

				var selecteduser;
				var j=0;
				if(newcat && newcat !=0){
					$("category-fields-wrap").show();

					var catlen = people[newcat].length;
					document.getElementById("xPersonAssignedTo_select").options[j]= new Option("'.hs_jshtmlentities(lg_request_assignedto_change).'","");
					j++;
					for(i=0; i < catlen; i++) {
						if(people[newcat][i]){
							newOptText=people[newcat][i][1];
							newOptValue=people[newcat][i][0];
							newOptText.indexOf = hs_indexOf;	//prototype
							document.getElementById("xPersonAssignedTo_select").options[j]= new Option(newOptText,newOptValue);
							// note default user for later
							if(newOptText.indexOf("('.lg_default.')") != -1){
								selecteduser = newOptValue;
							}
							j++;
						}
					}

					var j=0;
					if(reptags[newcat].length > 0){
						reptagshtml = "<div id=\"reporting-tag-list\">";
						$("reportingTags_wrap").show(); //Make sure rep tag area is shown
						var catlen = reptags[newcat].length;
						for(i=0; i < catlen; i++) {
							if(reptags[newcat][i]){

								reptagval 	= reptags[newcat][i][0];
								reptagtext 	= reptags[newcat][i][1];

								//Is tag active
								repchecked  = origreptags.inArray(reptagval) ? "active" : "";
								if(origreptags.inArray(reptagval)) reptagshtml = reptagshtml + "<input type=\"hidden\" id=\"reporting-tag-field-"+reptagval+"\" name=\"reportingTags[]\" value=\"" + reptagval + "\" />";

								reptagshtml = reptagshtml + "<div onClick=\"repTagChecked(\'"+reptagval+"\');\" id=\"reporting-tag-"+reptagval+"\" class=\"rt "+repchecked+"\" ><span class=\"rt-x\"></span><span class=\"rt-btn\"><table class=\"hideflow-table hand hideflow-tags\"><tr><td>" + reptagtext + "</td></tr></table></span></div>";

								//reptagshtml = reptagshtml + "<input type=\"checkbox\" onClick=\"repTagChecked(\'"+reptagval+"\');\" name=\"reportingTags[]\" id=\""+reptagval+"_box\" value=\"" + reptagval + "\" " + repchecked + "><span id=\"" + reptagval + "\" class=\""+ repclass +"\"> " + reptagtext + "</span><br>";
							}
						}
						reptagshtml = reptagshtml + "</div>";

						document.getElementById("reportingTags").innerHTML = reptagshtml;
					}else{
						$("reportingTags_wrap").hide(); //Make sure rep tag area is hidden if no tags
					}

					// check if we should override default user with the currently logged in user. Only do this after initial load when we are changing cats
					if(issetup == false && newcat != origcat){
                        var l = people[newcat].length;
						for(i=0;i < l;i++){
							if(people[newcat][i] && '.$user['xPerson'].' == people[newcat][i][0]){
                                selecteduser = people[newcat][i][0];
                            }
						}
                    }
                    var personList = people[newcat].entries();
                    var categoryStaffID = [];
                    for (person of personList) {
                       categoryStaffID.push(person[0]);
                      }

                    if(categoryStaffID.indexOf("'.$user['xPerson'].'") == -1 && '.(perm('fCanTransferRequests') ? '1' : '0') .' == 1 && '.(perm('fLimitedToAssignedCats') ? '1' : '0'). '==1) {
                        hs_alert("'.lg_request_transfer_warning.'");
                    }
					// set default user (already set on existing requests)
					if(selecteduser && !issetup && selecteduser != $F("xPersonAssignedTo")){
						//document.getElementById("xPersonAssignedTo_select").options[selecteduser].selected = true;
						changeAssignment(newcat,selecteduser);
					}

				}else{
					document.getElementById("reportingTags").innerHTML = "-";
				}

				//Setup custom fields for current category
				customFieldCategorySet();
		}

		function changeAssignment(xcategory,xperson){
			//Set field value
			$jq("#xPersonAssignedTo").val(xperson);

			//Loop over people in this category to find index position of the person
			var indexPos = 0;
			for(i=0;i<people[xcategory].length;i++){
				if(people[xcategory][i] && people[xcategory][i][0] == xperson) indexPos = i;
			}

			$jq("#xPersonAssignedTo_name").fadeOut(function(){
				$jq("#xPersonAssignedTo_name").html(people[xcategory][indexPos][3]);
				$jq("#xPersonAssignedTo_name").fadeIn();
			});

			$jq("#xPersonAssignedTo_img").fadeOut(function(){
				$jq("#xPersonAssignedTo_img").html(people[xcategory][indexPos][2]);
				$jq("#xPersonAssignedTo_img").fadeIn();
			});

			setSelectToValue("xPersonAssignedTo_select","",true);
		}

		function repTagChecked(rid){
			if($("reporting-tag-"+rid).hasClassName("active")){
				$("reporting-tag-"+rid).removeClassName("active");
				$("reporting-tag-field-"+rid).remove();
			}else{
				$("reporting-tag-"+rid).addClassName("active");
				$("reporting-tag-"+rid).insert({after:"<input type=\"hidden\" id=\"reporting-tag-field-"+rid+"\" name=\"reportingTags[]\" value=\"" + rid + "\" />"});
			}
		}

		function customFieldCategorySet(){
			var cats_customs = new Array();

			//Find current category
			var cat = $F("xCategory");
			';

            //List of custom fields for each cat
            $catsList->Move(0);
            while ($c = $catsList->FetchRow()) {
                $headscript .= 'cats_customs['.$c['xCategory'].'] = new Array("'.implode('","', hs_unserialize($c['sCustomFieldList'])).'");'."\n";
            }

            //Get custom field id's, exclude always visible ones
            $customfield_id = [];
            foreach ($customFields as $k=>$v) {
                if ($v['isAlwaysVisible'] == 0) {
                    $customfield_id[] = $v['xCustomField'];
                }
            }

    $headscript .= '
			//Set custom fields
			var fields = new Array("'.implode('","', $customfield_id).'");
			var field_ct = fields.length;

			//Hide all custom fields
			for(i=0;i<field_ct;i++){
				if($("Custom" + fields[i] + "_wrapper")) $("Custom" + fields[i] + "_wrapper").hide();
			}

			if(cat != 0){
				//Loop over fields and show the ones for this cat
				var cflen = cats_customs[cat].length;
				for(i=0;i<cflen;i++){
					if($("Custom" + cats_customs[cat][i] + "_wrapper")) $("Custom" + cats_customs[cat][i] + "_wrapper").show();
				}
			}
		}';

    $headscript .= '</script>';

//Request history
if ($reqid) {
    $onload .= 'initRequestHistory();';
}

$onload .= 'categorySet(true);';

//Email errors
if (isset($_GET['emailerror'])) {
    $headscript .= '
		<script type="text/javascript" language="JavaScript">
		$jq().ready(function(){
			hs_alert("'.hs_jshtmlentities($_GET['emailerror']).'");
		});
		</script>';
}

//If the user has selected to default to public then do so onload
if (isset($_GET['notetype'])) {
    $onload .= 'changeNote("'.$_GET['notetype'].'");';
    $default_notetype = $_GET['notetype'];
} elseif ($user['fDefaultToPublic']) {
    $onload .= 'changeNote("public");';
    $default_notetype = 'public';
} else {
    $onload .= 'changeNote("private");';
    $default_notetype = 'private';
}

//Email grouped and focus
$onload .= "build_email_list('cc');build_email_list('bcc');build_email_list('to');";

//Handle afterload events, some onloads don't seem to work in IE
if (isset($afterload)) {
    $onload .= '
		setTimeout(function(){
			'.$afterload.'
		},1000);';
}


/* Build Thermostat UI */
$thermoResponse = apiGetThermostatResponse($reqid);
$thermostat = '';
if ($thermoResponse) {
	$thermostat = '<div class="card" style="padding:20px;margin-top:30px;">';
	$thermostat .= '<div class="thermo-response thermo-response-' . apiGetResponseType($thermoResponse['iScore'], $thermoResponse['type']) . '">
						<div class="thermo-response-pill">
							<div class="survey-type">' . $thermoResponse['type'] . '</div>
							<span>' . $thermoResponse['iScore'] . '</span>
						</div>
						<div class="thermo-response-feedback">' . hs_htmlspecialchars($thermoResponse['tFeedback']) . '</div>
						<div class="thermo-response-link"><a href="https://thermostat.io/survey/manage/' . $thermoResponse['xSurvey'] . '#results">' . hs_lang('lg_admin_thermostat_label_see_results', 'See Survey Results') . '</a></div>
					</div>';
	$thermostat .= '<div style="margin-top:30px;display:flex;justify-content:center;"><img src="' . static_url() . '/static/img5/thermostat.png" style="height:20px; position: relative; top: 2px;" /></div></div>';
}

/*****************************************
PAGE OUTPUTS
*****************************************/
// If they are subscribed already just show it.
$subscribe_button = '';
if ($reqid && (in_array($user['xPerson'], $reqSubscribers) or $user['xPerson'] != $fm['xPersonAssignedTo'])) {
    $subscribe_button .= '<div onclick="subscription();return false;" id="subscribe_button_text" '.(in_array($user['xPerson'], $reqSubscribers) ? 'class="is_subscribed"' : '').' title="'.lg_request_subscribebutton.'"><img src="'.(in_array($user['xPerson'], $reqSubscribers) ? static_url().'/static/img5/star-solid-active.svg' : static_url().'/static/img5/star-light.svg').'" style="height:18px;" /></div>';
}

$page_header_title = '
    <span class="table-top-bold" style="font-size: 22px;">'.($reqid ? $reqid : ($batch ? lg_request_batchreply : lg_request_newrequest)).'</span> '.$subscribe_button.'
';

$page_header_menu = '';

if ($reqid > 0) {

    $page_header_menu .= '
        <div class="table-top-menu">
        '.($reqid ? renderOptionMenuButton("request-option-button").'
                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid, 'prev' => 'true']).'"><img src="'.static_url().'/static/img5/navigate-back.svg" style="height:24px;" title="'.lg_prev.'" /></a>
                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid, 'next' => 'true']).'"><img src="'.static_url().'/static/img5/navigate-forward.svg" style="height:24px;" title="'.lg_next.'" /></a>' : '').'
        </div>

        <div id="request_options_tmpl" style="display:none;">
            <ul class="tooltip-menu">
                <li class="tooltip-menu-divider"><a class="request_calendar_button hand '.$reminderct_label. '" onclick="openReminders();"><span class="tooltip-menu-maintext">'.hs_htmlspecialchars(lg_request_reminderbutton).'</span></a></li>
                '.(perm('fCanMerge') ? '<li><a href="#" onclick="merge_request_id();return false;"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_request_mergebutton).'</span></a></li>' : '').'
                <li class="tooltip-menu-divider"><a href=#"" onclick="hs_overlay(\'access_key_box\',{width:400});return false;"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_request_passwords).'</span></a></li>
                '.(perm('fCanManageTrash') && $fm['fTrash'] == 0 ? '<li class="tooltip-menu-divider"><a href="#" onclick="return hs_confirm(\''.hs_jshtmlentities(lg_request_trashconfirm).'\',\''.route('admin', ['pg' => 'request', 'reqid' => $reqid, 'trash' => '1']).'\');"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_request_movetotrash).'</span></a></li>' : '').'
                <li class="tooltip-menu-divider"><a href="#" onclick="goPage(\'admin?pg=request.static&reqid='.$reqid.'\');"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_request_print).'</span></a></li>
                <!--<li class="tooltip-menu-divider"><a href="#" onclick="$jq(\'#portal_link_append\').val(\'index.php?pg=request.check&id='.$reqid.$fm['sRequestPassword'].'\');$(\'portal_popup_menu\').prototip.show();return false;"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_request_viewinportal). '</span></a></li>-->
            </ul>
        </div>

        <div id="access_key_box" style="display:none;">
            <p style="padding:20px;">'.lg_request_accesskey.' <b style="letter-spacing:2px;">'.$reqid.$fm['sRequestPassword'].'</b></p>
        </div>

        <script type="text/javascript">
            new Tip("request-option-button", $("request_options_tmpl"),{
                    title: "",
                    border: 0,
                    radius: 0,
                    className: "hstinytipfat",
                    stem: false,
                    showOn: "click",
                    hideOn: false,
                    hideAfter: 1,
                    hideOthers: true,
                    width: "auto",
                    offset:{x:7,y:0},
                    hook: { target: "bottomRight", tip: "topRight" }
                });
        </script>';
}

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

if ($fm['fTrash'] == 1) {
    $feedbackArea = displaySystemBox(lg_request_trashbox, 'hdtrashbox');
}

if(! empty($reqid) && Request::reachedHistoryLimit($reqid)) {
    $feedbackArea = displaySystemBox(lg_request_fb_history_limit) . $feedbackArea;
}

//Request form
$customerInfo = '';
if(!$batch){
    $customerInfo = '
        <div id="customer-bar" class="">
            '.$feedbackArea.'
            <div class="isurgent '.($fm['fUrgent'] == 1 ? '' : 'hidden').'">'.lg_request_isurgent.'</div>
            <div class="tab_wrap">

                <ul class="tabs" id="customer-tabs">
					<li><a href="#customer_tab"><span>'.lg_request_headcust.'</span></a></li>
					<li><a href="#history_tab"><span>'.lg_request_historysearch.' '.(isset($openreqcount_label) ? $openreqcount_label : '').'</span></a></li>
                    '.(hs_setting('cHD_LIVELOOKUP') == 1 ? '<li><a href="#livelookup_tab"><span>'.lg_request_livelookup.'</span></a></li>' : '').'
                </ul>

                <div name="customer_tab" id="customer_tab" class="">
                    <div style="display: grid;grid-template-columns: repeat(3,minmax(0,1fr));grid-column-gap: 1rem;column-gap: 1rem;">
                        <div style="display:flex;">
                            <input tabindex="101" name="sFirstName" id="sFirstName" class="customer-field" type="text" size="" autocomplete="off" style="width:100%;" value="'.formClean($fm['sFirstName']).'" placeholder="'.lg_request_fname.'">
                        </div>
                        <div style="display:flex;">
                            <input tabindex="102" name="sLastName" id="sLastName" class="customer-field" type="text" size="" autocomplete="off" style="width:100%;" value="'.formClean($fm['sLastName']).'" placeholder="'.lg_request_lname.'">
                        </div>
                        '.(empty($reqid) ? '<div style="display:flex;"><select tabindex="106" name="fOpenedVia" id="fOpenedVia" style="width:100%;" class="customer-field '.errorClass('fOpenedVia').'">'.$contactSelect.'</select></div>'.errorMessage('fOpenedVia') : '<div class="opened-via" style="width:100%;">'.$GLOBALS['openedVia'][$fm['fOpenedVia']].($portal_name ? '<br /><span style="font-size:11px;">'.$portal_name.'</span>' : '').'</div>').'

                        <div style="display:flex;">
                            <input tabindex="103" name="sEmail" id="sEmail" class="customer-field" type="text" size="" autocomplete="off" style="width:100%;" value="'.formClean($fm['sEmail']).'" placeholder="'.lg_request_email.'">
                        </div>
                        <div style="display:flex;">
                            <input tabindex="104" name="sPhone" id="sPhone" class="customer-field" type="text" size="" autocomplete="off" style="width:100%;" value="'.formClean($fm['sPhone']).'" placeholder="'.lg_request_phone.'">
                        </div>
                        <div style="display:flex;">
                            <input tabindex="105" name="sUserId" id="sUserId" class="customer-field" type="text" size="" autocomplete="off" style="width:100%;" value="'.formClean($fm['sUserId']).'" placeholder="'.lg_request_custid.'">
                        </div>
                    </div>
                </div>

				<div name="livelookup_tab" id="livelookup_tab" style="display:none;">
                    <div id="customer_ajax_ll"></div>
                </div>

                <div name="history_tab" id="history_tab" style="display:none;">

                    <table class="ft ft-actions">
                        <tr class="trr">
                            <td class="tdl tdl-short"><label class="datalabel">'.lg_request_searchtype.'</label></td>
                            <td class="tdr">
                                <select id="request_history_search_type" onchange="doHistorySearch($F(this));" style="width="100%">
                                    <option value="1" '.selectionCheck(1, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search1.'</option>
                                    <option value="2" '.selectionCheck(2, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search2.'</option>
                                    <option value="4" '.selectionCheck(4, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search4.'</option>
                                    <option value="3" '.selectionCheck(3, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search3.'</option>
                                    <option value="7" '.selectionCheck(7, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search7.'</option>
                                    <option value="6" '.selectionCheck(6, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search6.'</option>
                                    <option value="5" '.selectionCheck(5, hs_setting('cHD_DEFAULT_HISTORYSEARCH')).'>'.lg_request_search5.'</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div id="history_frame_wrapper" style="display:none;">
                        <iframe style="" name="history_frame" id="history_frame" width="100%" height="400" src="" frameborder="no" scrolling="yes">Sorry, you need inline frames to fully see this page.</iframe>

                        <div style="padding-top: 8px;">
                            <button type="button" class="btn inline-action accent" onClick="insertCusDataFromHisSearch();">'.lg_request_insertdata.'</button>
                            '.(perm('fCanMerge') ? '<button type="button" class="btn inline-action" onClick="merge_request(window.frames[\'history_frame\'].document.forms[\'requestform\'].elements[\'xRequest\'].value);">'.lg_request_mergerequest.'</button>' : '').'
                            '.lg_or.' <a href="javascript:returnToSearchResults();" class="red">'.lg_request_returntosearch.'</a>
                        </div>
                    </div>

                    <div id="customer_ajax_history" style="padding:0px;"></div>

                </div>

            </div>

            <script>
            <!-- placed inline so that tabs immediately load -->
                new Control.Tabs($("customer-tabs"),{
                    beforeChange: function(){

                    },
                    afterChange: function(container){
                        //Run function based on which tab is being called
                        if(container.id == "history_tab"){
                            $("customer_ajax_history").show();
                            $("customer_ajax_history").update(ajaxLoading());
                            doHistorySearch( $F("request_history_search_type") );
                        }else if(container.id == "livelookup_tab"){
                            doLiveLookup( 0 );
                        }
                    }
                });
            </script>

        </div>
    ';
}

if (hs_setting('cHD_TIMETRACKER') && ! $batch) {
    $timeui = '';
    $time_personlist = '';
    $allStaff = apiGetAssignStaff();
    if (perm('fCanViewOwnReqsOnly') || perm('fLimitedToAssignedCats')) { //If in limited access mode they can only add time for themselves
        // Get all available staff and add this user so they can assign to themselves.
        $availableStaff = array_merge(apiGetStaffInUserCats($user['xPerson']), [(int) $user['xPerson']]);
        foreach ($allStaff as $s) {
            if (! in_array($s['xPerson'], $availableStaff)) {
                continue;
            }
            if ($s['fDeleted'] == 0) {
                $time_personlist .= '<option value="'.$s['xPerson'].'" '.selectionCheck($s['xPerson'], $user['xPerson']).'>'.$s['sFname'].' '.$s['sLname'].'</option>';
            }
        }
    } else {
        foreach ($allStaff as $s) {
            if ($s['fDeleted'] == 0) {
                $time_personlist .= '<option value="'.$s['xPerson'].'" '.selectionCheck($s['xPerson'], $user['xPerson']).'>'.$s['sFname'].' '.$s['sLname'].'</option>';
            }
        }
    }

    $tt_time = apiGetTimeTotal($_GET['reqid']);
    $timeui .= displayContentBoxTop(lg_request_timetracker.'<div id="time-tracker-total">'.($tt_time ? parseSecondsToTime($tt_time) : '0:00').'</div>', '', '', '100%', 'card box-noborder box-no-top-margin', 'box_body_tight_top');
    //$pagebody .= '<form id="time_form" onSubmit="logTime();return false;" style="margin:0px;">' . csrf_field();
    $timeui .= '
            <div style="display:flex;justify-content:space-between;">
                <div style="flex:3;display:flex;">
                    <input type="text" style="flex:3" name="tDescription" id="tDescription" placeholder="'.lg_request_timedesc.'" onFocus="clearFocusFill(this,\''.lg_request_timedesc.'\',\'textbox_normal\');" onkeypress="return noenter(event,\'time-tracker-log-time\');">
                    <input type="text" style="flex:1;margin-left:10px;" name="tTime" id="newtTime" size="8" placeholder="'.lg_request_timehrmin.'" onFocus="clearFocusFill(this,\''.lg_request_timehrmin.'\',\'textbox_normal\');" onkeypress="return noenter(event,\'time-tracker-log-time\');"><span id="seconds"></span>
                </div>
                <div style="flex:1;display: flex;justify-content: flex-end;align-items: center;">
                    <button id="timerimg" class="btn inline-action" onClick="toggleTimer();return false;" style="margin-left:10px;width: 100%;">'.lg_request_starttimer.'</button>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;">
                <div style="flex:1;display:flex;align-items:center;">
                    <input type="checkbox" name="fBillable" id="fBillable" value="1" />
                    <label class="datalabel hand" style="display:inline;" for="fBillable" style="margin-left:4px;">'.lg_request_billable.'</label>
                </div>
                <div style="flex:1;display:flex;justify-content: flex-end;">
                    <div class="button-bar" style="margin-top:0;">
                        <button id="time-tracker-options" class="btn inline-action secondary" onclick="$jq(\'#timetracker-options\').toggle();$(this).hide();return false;">'.lg_request_timetrackeroptions.'</button>
                        '.($reqid ? '<button id="time-tracker-log-time" type="button" name="submit" class="btn accent inline-action" onClick="logTime();" style="margin-right:31px;">'.lg_request_logtime.'</button>' : '').'
                    </div>
                </div>
            </div>

            <div id="timetracker-options" style="display:none;">
                <div style="display:flex;justify-content:space-between;">
                    <div style="flex:1;">
                        <select name="xPerson">'.$time_personlist.'</select>
                    </div>
                    <div style="flex:1;">
                        '.calinput('dtGMTDate', time()).'
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                $jq(document).ready(function(){
                    $jq("#time-tracker-total").click(function(){
                        hs_overlay("time_body",{width:620, buttons:[]});
                    });
                });
            </script>
        ';
    //$pagebody .= '</form>';

    $rows = apiGetTimeForRequest($_GET['reqid']);
    $timeui .= '<div style="display:none">';
    $timeui .= '<div id="time_body" style="display:flex;">';
    $timeui .= (hs_rscheck($rows) && $rows->RecordCount() > 0 ? renderTimeTrackerTable($rows) : '<div style="min-height: 30px;line-height:30px;text-align:center;padding:8px;">'.lg_request_timetrackerempty.'</div>');
    $timeui .= '</div></div>';

    $timeui .= displayContentBoxBottom();
}

$isBatchText = ($batch) ? 'true' : 'false';
$pagebody .= '
<div style="visibility:hidden; opacity:0" id="dropzone-cover">
    <div id="textnode">Drop to upload</div>
</div>
<div class="dz-message-container"><h1>'.lg_request_drop_here.'</h1></div>
<form action="'.($batch ? route('admin', ['pg' => 'request.batch']) : route('admin', ['pg' => 'request', 'reqid' => $reqid])).'" method="POST" enctype="multipart/form-data" name="requestform" id="requestform" onSubmit="return checkform();">';
$pagebody .= csrf_field();
$pagebody .= '
	<div id="request-wrapper">
		<div id="request-column">
            '.$customerInfo.'
            '.$timeui.'
            <div class="card mb noprint" style="min-width: 530px;padding-top: 6px;">
                <div class="card-inner">
                    <div style="display: flex;justify-content: space-between;flex-wrap: wrap;">
						<div class="note-menu" style="display: flex;">
							<div>
								<input type="text" id="responses-search-box-small-q" class="no-submit" value="" placeholder="'.lg_request_searchresponses.'" autocomplete="off" tabindex="-1" onfocus="hs_shortcutsOff();" onblur="hs_shortcutsOn();" style="">

								<script>
									$jq(document).ready(function() {
										$jq(".no-submit").keypress(function(e){
											if ( e.which == 13 ) { e.preventDefault(); }
										});

										new Ajax.Autocompleter("responses-search-box-small-q","search-box-small-autocomplete", "admin?pg=ajax_gateway&action=response_search", {
											paramName:"search"
											, minChars: 1
											, frequency:0.3
											, updateElement: function(sel){
													getResponse($(sel).down("span").innerHTML);$("responses-search-box-small-q").value="";
											}
										});
									});
								</script>

								</div>
							<div id="">
                                <span class="hand" id="response_mil_menu" onclick="popup(\'response_mil_menu\',\'response_mil_menu\',2)">'.lg_request_appresponse. '</span>
                            </div>
                            <div id="">
                                <span class="hand" id="kb_mil_menu" onclick="popup(\'kb_mil_menu\',\'kb_mil_menu\',2)">'.lg_request_insertkblink.'</span>
                            </div>
                            '.($drafts ? '<div style="cursor:pointer;" href="" onclick="draft_options_box();return false;">'.lg_request_notedrafts.' <span id="draft_count">'.$drafts->RecordCount().'</span></div>' : ''). '
                        </div>
                        <div class="note-menu">
                            <div id="email_customer_msg">'.hs_jshtmlentities(lg_request_emailcustomermsg).'</div>
                        </div>
                    </div>
            ';

        /*
        if ($reqid && ($editor_type == 'markdown' || $editor_type == 'none')) {
            $pagebody .= '<div style="float:right;"><a href="" class="btn inline-action" onclick="doNoteDraftSave();return false">'.lg_request_savedraft.'</a></div>';
        }
        */

        $pagebody .= '
        <textarea  placeholder="Add a note..." tabindex="107" id="tBody" name="tBody" cols="30" rows="4" style="height:200px;width:100%;box-sizing: border-box;" class="'.errorClass('tBody').'">'.formCleanHtml(trim($fm['tBody'])).'</textarea>'.errorMessage('tBody').'

        <div style="display:flex;justify-content: space-between;flex-wrap: wrap;">
            <div id="attach_files">
                <div class="fallback">
                    <input name="file" type="file" multiple />
                </div>
                <span><img src="'.static_url().'/static/img5/attachment.svg" style="margin-right:6px;height:15px;" />'.lg_request_attachbox.'</span>
            </div>
        </div>
        <div id="attachment_box"></div>

        <div style="display:flex;justify-content: space-between;flex-wrap: wrap;margin-top:10px;">
            <div class="request-sub-note-box button-bar button-bar-combo" style="margin-top:6px;margin-bottom:6px;">
                <button type="button" id="button-public" class="btn inline-action" onclick="changeNote(\'public\');">'.hs_jshtmlentities(lg_request_custupdate).'</button>
                <button type="button" id="button-private" class="btn inline-action" onclick="changeNote(\'private\');">'.hs_jshtmlentities(lg_request_custupdatepriv).'</button>
                <button type="button" id="button-external" class="btn inline-action" onclick="changeNote(\'external\');">'.hs_jshtmlentities(lg_request_custupdateexternal).'</button>
            </div>
            <div class="button-bar" style="margin-top:0;">'.$buttons.'</div>
        </div>

		<input type="hidden" name="vmode" value="1">
		<input type="hidden" name="reqid" id="reqid" value="'.$reqid.'">
		<input type="hidden" name="xOpenedViaId" id="xOpenedViaId" value="'.($fm['xOpenedViaId'] ? $fm['xOpenedViaId'] : '').'">
        <input type="hidden" name="xPortal" id="xPortal" value="'.($fm['xPortal'] ? $fm['xPortal'] : '').'">
		<input type="hidden" name="xMailboxToSendFrom" value="'.$fm['xMailboxToSendFrom'].'" />
		<input type="hidden" name="sRequestPassword" value="'.formClean($fm['sRequestPassword']).'">

        </div> <!-- end of card inner -->';

        $pagebody .=
            '<div class="drawer">
                <div id="request-drawer" class="inner" style="display:none;">
                    <div id="request-option-wrap" class="request-option-wrap request-option-wrap-'.$default_notetype.'">
                        <div id="note_option_div"></div>
                    </div>
                </div>
                <div class="bar" onclick="$jq(\'#request-drawer\').toggle();">
                    <div class="title">'.($fm['sTitle'] ? '<b style="color:'.(inDarkMode() ? '#cfd0d1' : '#737373').'">'.lg_request_subject.':</b> ' : '') . '<span id="js-subject">'.hs_htmlspecialchars($fm['sTitle']).'</span></div>
                    <div class="button" style="font-weight:bold;color:'.(inDarkMode() ? '#cfd0d1' : '#737373').'">
						<img src="'.static_url().'/static/img5/paper-plane-solid.svg" style="height:14px;" title="" />
						&nbsp;&nbsp; '.lg_request_emailoptions.'
                    </div>
                </div>
            </div>';

			// On an existing request open email options drawer if there are staff, cc's, or bcc's #395
			if ($fm['emailtogroup'] || $email_groups['last_cc'] || $email_groups['last_bcc']) {
				$pagebody .= '<script>$jq("#request-drawer").toggle();</script>';
			}

        $pagebody .= '</div>'; // end of note wrap card

        if (! $batch && $reqid) {
            $pagebody .= '
            <div class="">
                <div style="display:flex;justify-content: space-between;align-items: center;margin-bottom: 12px;">
                    <div class="request_history_header">'.lg_request_requesthistory.'</div>
                    <div>
                        <a class="btn tiny link" style="color: #737373;" onclick="hs_quote_public('.$reqid.',\'tBody\');return false;">'.lg_request_quotepublic.'</a>
                        <select onchange="changeRequestHistoryView();" id="changeRequestHistoryDropdown" style="opacity: 0.65;height: 22px;font-size: 12px;padding: 1px 24px 1px 8px;color: #535353;font-weight: 700;background-color: transparent;border: none;opacity: 1;" class="">
                            <option value="1" '.selectionCheck(1, $user['fRequestHistoryView']).'>'.lg_request_show.': '.lg_request_fullview.'</option>
                            <option value="4" '.selectionCheck(4, $user['fRequestHistoryView']).'>'.lg_request_show.': '.lg_request_justnotes.'</option>
                            <option value="2" '.selectionCheck(2, $user['fRequestHistoryView']).'>'.lg_request_show.': '.lg_request_publicview.'</option>
                            <option value="3" '.selectionCheck(3, $user['fRequestHistoryView']).'>'.lg_request_show.': '.lg_request_fileview.'</option>
                        </select>
                    </div>
                </div>

                <div id="request_history_body"><div style="display: flex;justify-content: center;"><script>document.write(ajaxLoading());</script></div></div>
            </div>';

        } else {
            $pagebody .= $batchtable;
        }

        $pagebody .= '</div>'; // end of request column

        $pagebody .= '<div id="request-details">';

        $pagebody .= renderPageheader($page_header_title, $page_header_menu, false, true);

        $pagebody .= '<div id="person_status_notification_wrapper" style="display:none;"></div>';

        //Note about request batching
        if ($batch) {
            $pagebody .= displayContentBoxTop(lg_request_batchwarninghead, '', '', '100%', 'card box-no-top-margin batch-info').'
			'.lg_request_batchwarning. '
			<div class="hr"></div>
			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="build_batch_filter" class="datalabel">' . lg_request_batchwarning2 . '</label>
				</div>
				<div class="control" style="flex: 0">
					<input type="checkbox" name="build_batch_filter" id="build_batch_filter" class="checkbox" value="1">
					<label for="build_batch_filter" class="switch"></label>
				</div>
			</div>
			';

            $pagebody .= displayContentBoxBottom();
        }

            if ($fm['xPersonAssignedTo'] == 0) {
                $default_photo = '<img src="'.static_url().'/static/img5/inbox-in-solid.svg" style="width:48px;height:48px;" />';
            } else {
                $avatar = new HS\Avatar\Avatar();
                $default_photo = $avatar->xPerson($fm['xPersonAssignedTo'])->html();
            }

            $pagebody .= '
            <div class="detail-fields">
    			<div class="field-wrap">
    				<label class="datalabel" for="xStatus">'.lg_request_status.'</label>
    				<select tabindex="108" name="xStatus" id="xStatus" class="'.errorClass('xStatus').'">'.$statusSelect.'</select>'.errorMessage('xStatus').'
    			</div>

    			<div class="field-wrap">
    				<label class="datalabel" for="xCategory">'.lg_request_category.'</label>
    				<select tabindex="109" onChange="categorySet();" name="xCategory" id="xCategory" class="'.errorClass('xCategory').'">'.categorySelectOptions($catsList, $fm['xCategory']).'</select>'.errorMessage('xCategory').'
    			</div>

    			<div id="category-fields-wrap" style="display:none;">

    				<div class="field-wrap">
    					<label class="datalabel" for="xPersonAssignedTo_select">'.lg_request_assignedto.'</label>
    					<table style="width: 100%;">
    						<tr valign="top">
    							<td>
    								<div class="user-icon-wrap" id="xPersonAssignedTo_img" style="padding-left:0;margin-top: 5px;">'.$default_photo.'</div>
    							</td>
    							<td style="padding-left:8px;">
    								<div id="xPersonAssignedTo_name" class="request-assignedto-name">'.($fm['xPersonAssignedTo'] > 0 ? $allStaff[$fm['xPersonAssignedTo']]['sFname'].' '.$allStaff[$fm['xPersonAssignedTo']]['sLname'] : lg_inbox).'</div>
    								<select tabindex="110" name="xPersonAssignedTo_select" id="xPersonAssignedTo_select" style="width:100%;margin-top:0;" onchange="changeAssignment($F(\'xCategory\'),$F(\'xPersonAssignedTo_select\'));" class=" '.errorClass('xPersonAssignedTo').'">'.$assignSelect.'</select>'.errorMessage('xPersonAssignedTo').'
    								<input type="hidden" name="xPersonAssignedTo" id="xPersonAssignedTo" value="'.$fm['xPersonAssignedTo'].'" />
    							</td>
    						</tr>
    					</table>
    				</div>

    				<div id="reportingTags_wrap">
    					<div class="field-wrap">
    						<label class="datalabel" for="reportingTags" style="margin-bottom:8px;">'.lg_request_reportingtags.'</label>

    						<div class="scrollbar_container" style="width:100%">
    							<div id="reportingTags_track" class="scrollbar_track" style="display:none;"><div id="streamview_scrollbar_handle" class="scrollbar_handle"></div></div>
    							<div id="reportingTags" class="scrollbar_content" style="width:100%"></div>
    						</div>
    					</div>
    				</div>

    				'.(! empty($customfieldsdisplay) ? '<div class="nice-line"></div>' : '').'

					'.$customfieldsdisplay. '

    			</div> <!-- category wrap -->

                <button type="button" onclick="urgentChange();" id="make-urgent" style="margin-top:8px;" class="btn inline-action '.($fm['fUrgent'] == 1 ? 'isurgent' : '').'"><span>'.($fm['fUrgent'] == 1 ? lg_request_isurgent : lg_request_isnoturgent).'</span></button>
                <input type="hidden" name="fUrgent" id="fUrgent" value="'.formClean($fm['fUrgent']).'" />

				' . $thermostat . '

              </div>

              '.$pushui.'
    	   </div>
        </div> <!-- end request wrapper -->
</form>';

$address_btn = '<button type="button" class="btn inline-action" onclick="openAddressBook();return false;">'.hs_jshtmlentities(lg_addressbook_title).'</button>';

$pagebody .= '
<div id="draft_options_box" style="display:none;"></div>
<div id="kbui_box" style="display:none; width: 100%"></div>

<script type="text/html" id="noteoptions_tmpl">
	<input type="hidden" id="external_note" name="external_note" value="0">
	<input type="hidden" name="fPublic" id="fPublic" value="1">

    <div id="public_note_options_table">

        <div class="request-options-group">
            <div class="request-options-group-inner">
                <div class="request-options-group-field">
                    <label for="sTitle" class="request-options-group-label">'.hs_jshtmlentities(lg_request_emailsubject).'</label>
                    <input type="text" name="sTitle" id="sTitle" size="35" style="width:100%;" value="'.hs_htmlspecialchars($fm['sTitle']).'" onchange="subjectChange()">
                </div>
            </div>
        </div>

        <div class="request-options-group">
            <div class="request-options-group-inner">
                <div class="request-options-group-field">
                    <label for="emailfrom" class="request-options-group-label">'.hs_jshtmlentities(lg_request_sendemailfrom).'</label>
                    '.$mailboxesSelect.'
                </div>
            </div>
        </div>

        '.(! empty($reqSubscribersList) ? '
            <div class="request-options-group" id="" style="">
                <div class="request-options-group-inner">
                    <div class="request-options-group-field">
                        <label for="subscribers" class="request-options-group-label">'.hs_jshtmlentities(lg_request_subscribers).'</label>
                        '.$reqSubscribersList.'
                    </div>
                </div>
            </div>'
            : ''
        ).'

        <div id="ccstaff_option" class="request-options-group hidden" style="display:none;">
            <div class="request-options-group-inner">
                <div class="request-options-group-field">
                    <label for="ccstaffgroup" class="request-options-group-label" style="">'.hs_jshtmlentities(lg_request_notifystafftab).'</label>
                    <div id="ccstaff_public" style="width:100%;display:none;">'.renderSelectMulti('ccstaff_public', $staffList, [], '', 'ccstaff', true).'</div>

                    <div style="display:flex;flex-direction:column;width: 100%;">
                        <a href="" id="ccstaff_button" class="btn inline-action" style="" onclick="$(\'ccstaff_public\').show();$jq(this).hide();return false;">
                            '.hs_jshtmlentities(lg_request_addstaff).'
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="request-options-group" id="hidden_external" style="display:none;">
            <div class="request-options-group-inner">
                <div class="request-options-group-field">
                    <label for="emailtogroup" class="request-options-group-label">'.hs_jshtmlentities(lg_request_emailto).'</label>
                    <input type="hidden" name="emailtogroup" id="emailtogroup" value="'.hs_jshtmlentities(($fm['emailtogroup'] ? $fm['emailtogroup'] : $email_groups['last_to'])).'" />
                    <input type="hidden" name="emailtogroup_inactive" id="emailtogroup_inactive" value="'.hs_jshtmlentities(($fm['emailtogroup_inactive'] ? $fm['emailtogroup_inactive'] : $email_groups['inactive_to'])).'" />

                    <div style="display:flex;flex-direction:column;width: 100%;">
                        <div>
                            <div id="emailtogroup_list" class="notification_list"></div>
                            <span style="display:none" id="addto_box">
                                <span style="display:flex;margin-bottom:5px;">
                                    <input type="text" name="addto_email" id="addto_email" style="width: 100%;margin-right:10px;" size="" value="" onkeypress="return noenter(event,\'addto_button\');" />
                                    <div class="button-bar right" style="margin-top:0;">
                                        <button type="button" class="btn inline-action" style="" name="addto_button" id="addto_button" onclick="add_email(\'to\');$(\'addto_box\').toggle();">'.hs_jshtmlentities(lg_add).'</button>
                                        '.$address_btn.'
                                    </div>
                                </span>
                            </span>
                        </div>

                        <a href="" class="btn inline-action" id="addto_link" onclick="$(\'addto_box\').toggle();$(\'addto_email\').focus();return false;">'.hs_jshtmlentities(lg_request_addtoemail).'</a>

                    </div>
                </div>
            </div>
        </div>

        <div class="request-options-group hidden" id="hidden-ccs" style="display:none;">
            <div class="request-options-group-inner">
                <div class="request-options-group-field">
                    <label for="emailccgroup" class="request-options-group-label">'.hs_jshtmlentities(lg_request_emailcc).'</label>
                    <input type="hidden" name="emailccgroup" id="emailccgroup" value="'.($fm['emailccgroup'] ? $fm['emailccgroup'] : $email_groups['last_cc']).'" />
                    <input type="hidden" name="emailccgroup_inactive" id="emailccgroup_inactive" value="'.hs_jshtmlentities(($fm['emailccgroup_inactive'] ? $fm['emailccgroup_inactive'] : $email_groups['inactive_cc'])).'" />

                    <div style="display:flex;flex-direction:column;width: 100%;">
                        <div>
                            <div id="emailccgroup_list" class="notification_list"></div>
                            <span style="display:none" id="addcc_box">
                                <span style="display:flex;margin-bottom:5px;">
                                    <input type="text" name="addcc_email" id="addcc_email" style="width: 100%;margin-right:10px;" size="" value="" onkeypress="return noenter(event,\'addcc_button\');" />
                                    <div class="button-bar right" style="margin-top:0;">
                                        <button type="button" class="btn inline-action" style="" name="addcc_button" id="addcc_button" onclick="add_email(\'cc\');$(\'addcc_box\').toggle();">'.hs_jshtmlentities(lg_add).'</button>
                                        '.$address_btn.'
                                    </div>
                                </span>
                            </span>
                        </div>

                        <a href="" class="btn inline-action" id="addcc_link" onclick="$(\'addcc_box\').toggle();$(\'addcc_email\').focus();return false;">'.hs_jshtmlentities(lg_request_addccemail).'</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="request-options-group hidden" id="hidden-bccs" style="display:none;">
            <div class="request-options-group-inner">
                <div class="request-options-group-field">
                    <label for="emailbccgroup" class="request-options-group-label">'.hs_jshtmlentities(lg_request_emailbcc).'</label>
                    <input type="hidden" name="emailbccgroup" id="emailbccgroup" value="'.hs_jshtmlentities(($fm['emailbccgroup'] ? $fm['emailbccgroup'] : $email_groups['last_bcc'])).'" />
                    <input type="hidden" name="emailbccgroup_inactive" id="emailbccgroup_inactive" value="'.hs_jshtmlentities(($fm['emailbccgroup_inactive'] ? $fm['emailbccgroup_inactive'] : $email_groups['inactive_bcc'])).'" />

                    <div style="display:flex;flex-direction:column;width: 100%;">
                        <div>
                            <div class="notification_list" id="emailbccgroup_list"></div>
                            <div style="display:none;" id="addbcc_box">
                                <span style="display:flex;margin-bottom:5px;">
                                    <input type="text" name="addbcc_email" id="addbcc_email" style="width: 100%;margin-right:10px;" size="" value="" onkeypress="return noenter(event,\'addbcc_button\');" />
                                    <div class="button-bar right" style="margin-top:0;">
                                        <button type="button" class="btn inline-action" style="" name="addbcc_button" id="addbcc_button" onclick="add_email(\'bcc\');$(\'addbcc_box\').toggle();">'.hs_jshtmlentities(lg_add).'</button>
                                        '.$address_btn.'
                                    </div>
                                </span>
                            </div>
                        </div>

                        <a href="" class="btn inline-action" id="addbcc_link" onclick="$(\'addbcc_box\').toggle();$(\'addbcc_email\').focus();return false;">'.hs_jshtmlentities(lg_request_addbccemail).'</a>
                    </div>

                </div>
            </div>
        </div>

    </div>

</script>
';
