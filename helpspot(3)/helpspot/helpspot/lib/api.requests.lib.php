<?php

// SECURITY: Don't allow direct calls

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

if (! defined('cBASEPATH')) {
    die();
}

/******************************************
ADD USER TO REQUEST
******************************************/
function apiAddEditRequest($req, $notify, $f, $l)
{
    // initialize
    $reqHis = 0;
    $reqID = '';
    $custfields = '';
    $custvalues = [];
    $custbinds = '';

    $req['mode'] = isset($req['mode']) ? $req['mode'] : 'add';
    // update fields
    $req['xRequest'] = hs_numeric($req, 'xRequest') ? $req['xRequest'] : 0;
    $req['xPerson'] = hs_numeric($req, 'xPerson') ? $req['xPerson'] : 0;
    $req['reassignedfrom'] = hs_numeric($req, 'reassignedfrom') ? $req['reassignedfrom'] : '';		//xPerson who is no longer assigned to request
    $req['fPublic'] = hs_numeric($req, 'fPublic') ? $req['fPublic'] : 0;
    $req['finitial'] = hs_numeric($req, 'fInitial') ? $req['fInitial'] : 0;
    $req['iTimerSeconds'] = hs_numeric($req, 'iTimerSeconds') ? $req['iTimerSeconds'] : 0;
    $req['tLog'] = isset($req['tLog']) ? $req['tLog'] : '';
    $req['dtGMTChange'] = hs_numeric($req, 'dtGMTChange') ? $req['dtGMTChange'] : 0;

    // new req fields
    $req['fOpenedVia'] = hs_numeric($req, 'fOpenedVia') ? $req['fOpenedVia'] : hs_setting('cHD_CONTACTVIA');
    $req['xOpenedViaId'] = hs_numeric($req, 'xOpenedViaId') ? $req['xOpenedViaId'] : 0;
    $req['xMailboxToSendFrom'] = hs_numeric($req, 'xMailboxToSendFrom') ? $req['xMailboxToSendFrom'] : 0;
    $req['xPersonOpenedBy'] = hs_numeric($req, 'xPersonOpenedBy') ? $req['xPersonOpenedBy'] : 0;
    $req['xPersonAssignedTo'] = hs_numeric($req, 'xPersonAssignedTo') ? $req['xPersonAssignedTo'] : 0;
    $req['fOpen'] = hs_numeric($req, 'fOpen') ? $req['fOpen'] : 1;
    $req['xStatus'] = hs_numeric($req, 'xStatus') ? $req['xStatus'] : hs_setting('cHD_STATUS_ACTIVE', 1);
    $req['xPortal'] = hs_numeric($req, 'xPortal') ? $req['xPortal'] : 0;
    $req['xCategory'] = hs_numeric($req, 'xCategory') ? $req['xCategory'] : 0;
    $req['dtGMTOpened'] = hs_numeric($req, 'dtGMTOpened') ? $req['dtGMTOpened'] : date('U');
    $req['dtGMTClosed'] = hs_numeric($req, 'dtGMTClosed') ? $req['dtGMTClosed'] : 0;
    $req['sTitle'] = isset($req['sTitle']) ? hs_strip_tags(hs_truncate($req['sTitle'], 255), true) : '';
    $req['fUrgent'] = hs_numeric($req, 'fUrgent') ? $req['fUrgent'] : 0;
    $req['fTrash'] = hs_numeric($req, 'fTrash') ? $req['fTrash'] : 0;
    $req['dtGMTTrashed'] = hs_numeric($req, 'dtGMTTrashed') ? $req['dtGMTTrashed'] : 0;

    $req['sUserId'] = isset($req['sUserId']) ? hs_strip_tags(hs_truncate($req['sUserId'], 80), true) : '';
    $req['sFirstName'] = isset($req['sFirstName']) ? hs_strip_tags(hs_truncate($req['sFirstName'], 40), true) : '';
    $req['sLastName'] = isset($req['sLastName']) ? hs_strip_tags(hs_truncate($req['sLastName'], 40), true) : '';
    $req['sEmail'] = isset($req['sEmail']) ? hs_strip_tags(hs_truncate($req['sEmail'], 100), true) : '';
    $req['sPhone'] = isset($req['sPhone']) ? hs_strip_tags(hs_truncate($req['sPhone'], 40), true) : '';

    $req['tBody'] = isset($req['tBody']) ? $req['tBody'] : '';
    $req['fNoteIsHTML'] = hs_numeric($req, 'fNoteIsHTML') ? $req['fNoteIsHTML'] : 0;
    $req['fNoteIsClean'] = isset($req['fNoteIsClean']) ? $req['fNoteIsClean'] : false; // If the note has already been through the HTML cleaner
    $req['tEmailHeaders'] = isset($req['tEmailHeaders']) ? $req['tEmailHeaders'] : '';
    $req['sRequestPassword'] = isset($req['sRequestPassword']) ? $req['sRequestPassword'] : '';
    $req['subscribe_all_ccstaff'] = isset($req['subscribe_all_ccstaff']) ? $req['subscribe_all_ccstaff'] : '';

    // Handle custom fields
    if (! empty($GLOBALS['customFields'])) {
        foreach ($GLOBALS['customFields'] as $v) {
            $custid = 'Custom'.$v['fieldID'];

            // if checkbox only allow 1 or 0
            if ($v['fieldType'] == 'checkbox' && $req[$custid] != 1) {
                $req[$custid] = 0;
            }

            if (isset($req[$custid])) {
                //if num/checkbox/decimal type don't allow if not numeric
                if (($v['fieldType'] == 'numtext' || $v['fieldType'] == 'checkbox' || $v['fieldType'] == 'decimal') && ! hs_numeric($req, $custid)) {
                    $req[$custid] = null;
                }

                //Check predefined list values, empty if not valid
                if ($v['fieldType'] == 'select') {
                    $listitems = hs_unserialize($v['listItems']);
                    if (! in_array($req[$custid], $listitems)) {
                        $req[$custid] = null;
                    }
                }

                // Truncate any varchar field types to their field length
                if (in_array($v['fieldType'], ['ajax', 'drilldown', 'regex', 'select', 'text'])) {
                    $fieldLength = (! $v['sTxtSize']) ? 255 : $v['sTxtSize'];
                    $req[$custid] = hs_truncate($req[$custid], $fieldLength);
                }

                //Convert dates to timestamps
                if ($v['fieldType'] == 'date' || $v['fieldType'] == 'datetime') {
                    $time_format = $v['fieldType'] == 'date' ? hs_setting('cHD_POPUPCALSHORTDATEFORMAT') : hs_setting('cHD_POPUPCALDATEFORMAT');
                    if (! hs_empty($req[$custid])) {
                        $time = is_numeric($req[$custid]) ? $req[$custid] : jsDateToTime($req[$custid], $time_format);
                        $req[$custid] = $time ? $time : null;

                        if ($v['fieldType'] == 'date' && ! is_null($req[$custid])) {
                            $req[$custid] = \Carbon\Carbon::createFromTimestamp($req[$custid])
                                ->startOfDay()
                                ->timestamp;
                        }
                    } else {
                        $req[$custid] = null;
                    }
                }

                // If it's empty set it to null.
                if ($req[$custid] === '') {
                    $req[$custid] = null;
                }

                if ($req['mode'] == 'add') {
                    $custfields .= ', '.$custid.' ';
                    $custvalues[] = $req[$custid];
                    $custbinds .= ',?';
                } else {
                    $custfields .= ', '.$custid.' =?';
                    $custvalues[] = $req[$custid];
                }
            }
        }
    }

    $transBad = false;
    $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

    if ($req['mode'] == 'add') {

        //Handle auto assignments
        $autoAssignedUser = false;
        if ($req['xPersonAssignedTo'] == 0) {
            $req['xPersonAssignedTo'] = apiAutoAssignStaff($req['xPersonAssignedTo'], $req['xCategory'], $f, $l);
            $autoAssignedUser = $req['xPersonAssignedTo'];
        }

        //Out of office
        $req['xPersonAssignedTo'] = apiOutOfOffice($req['xPersonAssignedTo']);

        //If they are closing, set the closed date.
        if ($req['fOpen'] == 0 and $req['dtGMTClosed'] == 0) {
            $req['dtGMTClosed'] = $req['dtGMTOpened'];
        }

        // Get request password
        $reqPassword = apiGetRequestPassword();

        //Add email to portal login
        apiPortalAddLoginIfNew($req['sEmail']);

        $reqRes = $GLOBALS['DB']->Execute('INSERT INTO HS_Request(fOpenedVia,xOpenedViaId,xMailboxToSendFrom,xPortal,xPersonOpenedBy,xPersonAssignedTo,fOpen,xStatus,fUrgent,
												xCategory,dtGMTOpened,dtGMTClosed,sRequestPassword,sTitle,sUserId,sFirstName,sLastName,sEmail,sPhone,iLastReplyBy,fTrash,dtGMTTrashed,
												sRequestHash '.$custfields.' )
											VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?'.$custbinds.')',
                                                  array_merge([$req['fOpenedVia'],
                                                        $req['xOpenedViaId'],
                                                        $req['xMailboxToSendFrom'],
                                                        $req['xPortal'],
                                                        $req['xPersonOpenedBy'],
                                                        $req['xPersonAssignedTo'],
                                                        $req['fOpen'],
                                                        $req['xStatus'],
                                                        $req['fUrgent'],
                                                        $req['xCategory'],
                                                        $req['dtGMTOpened'],
                                                        $req['dtGMTClosed'],
                                                        $reqPassword,
                                                        hs_strip_tags($req['sTitle']),
                                                        $req['sUserId'],
                                                        $req['sFirstName'],
                                                        $req['sLastName'],
                                                        $req['sEmail'],
                                                        $req['sPhone'],
                                                        $req['xPersonOpenedBy'],
                                                        $req['fTrash'],
                                                        $req['dtGMTTrashed'],
                                                        md5(trim($req['tBody'])), ], $custvalues));

        // Add initial body message as a request history. Do check to make sure it's an actual request.
        if ($reqRes) {
            $reqID = dbLastInsertID('HS_Request', 'xRequest');

            $reqHis = apiAddRequestHistory([
                'xRequest' => $reqID,
                'xPerson' => $req['xPersonOpenedBy'],
                'dtGMTChange' => $req['dtGMTOpened'],
                'fPublic' => $req['fPublic'],
                'fInitial' => 1,
                'iTimerSeconds' => $req['iTimerSeconds'],
                'tLog' => $req['tLog'],
                'tNote' => $req['tBody'],
                'fNoteIsHTML' => $req['fNoteIsHTML'],
                'fNoteIsClean' => $req['fNoteIsClean'],
                'tEmailHeaders' => $req['tEmailHeaders'],
            ]);

            //add reporting tags
            $reqReportingTags = (isset($req['reportingTags'])) ? $req['reportingTags'] : null;
            apiAddEditRequestRepTags($reqReportingTags, $reqID, $req['xCategory'], $f, $l);

            // Record Request Initial Values for Event Log
            $req['xRequest'] = $reqID;
            $eventCollection = HS\Domain\Workspace\EventCollection::toCollection($req, $reqHis);
            $eventCollection->flush($reqHis);

            //Trigger check
            //TODO: DO BIZ HOURS CHECK
            $user = apiGetLoggedInUser();
            try {
                apiRunTriggers($reqID, $req, false, $req['tBody'], $req['fPublic'], $user['xPerson'], 1, __FILE__, __LINE__);
            } catch (Exception $e) {
                Log::error($e);
            }

            //Log assignment chain
            if ($req['xPersonAssignedTo'] > 0) {
                logAssignmentChange($reqID, $req['xPersonAssignedTo'], lg_request_initassign);
            }
        } else {
            $transBad = true;
            $GLOBALS['DB']->FailTrans();
        }
    } else {
        //THIS SHOULD ONLY BE CALLED VIA THE CLASS.REQUESTUPDATE.PHP FILE. THATS THE FILE THAT CREATES THE LOGS FOR UPDATES
        //Update request
        $reqID = $req['xRequest'];

        $reqRes = $GLOBALS['DB']->Execute('UPDATE HS_Request
											SET xPersonAssignedTo=?, fOpen=?, xStatus=?, fUrgent=?, xCategory=?, dtGMTClosed=?, sUserId=?,
												sFirstName=?, sLastName=?, sEmail=?, sPhone=?, sTitle=?, xMailboxToSendFrom=?,fTrash=?, dtGMTTrashed=? '.$custfields.'
											WHERE xRequest = ?',
                                                array_merge([$req['xPersonAssignedTo'],
                                                    $req['fOpen'],
                                                    $req['xStatus'],
                                                    $req['fUrgent'],
                                                    $req['xCategory'],
                                                    $req['dtGMTClosed'],
                                                    $req['sUserId'],
                                                    $req['sFirstName'],
                                                    $req['sLastName'],
                                                    $req['sEmail'],
                                                    $req['sPhone'],
                                                    $req['sTitle'],
                                                    $req['xMailboxToSendFrom'],
                                                    $req['fTrash'],
                                                    $req['dtGMTTrashed'], ], $custvalues, [$reqID]));

        // Add history log event
        if ($reqRes) {
            // HS_Request_Events filled in class.requestupdate.php,
            // this tLog can be passed through
            $reqHis = apiAddRequestHistory([
                'xRequest' => $reqID,
                'xPerson' => $req['xPerson'],
                'dtGMTChange' => $req['dtGMTChange'],
                'tLog' => $req['tLog'],
                'request_events' => (isset($req['request_events'])) ? $req['request_events'] : [],
            ]);
        } else {
            $transBad = true;
            $GLOBALS['DB']->FailTrans();
        }

        // Get request password
        $reqPassword = ! empty($req['sRequestPassword']) ? $req['sRequestPassword'] : apiGetRequestPassword($reqID);
    }

    $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/

    if (! $transBad) {
        app('events')->flush('request.history.create');

        // If we should notify then do so, unless we think it's spam
        if ($notify == 1 && $req['xStatus'] != hs_setting('cHD_STATUS_SPAM', 2)) {
            if ($req['mode'] == 'add') {
                $notifier = new hs_notify($reqID, $reqHis, $req['xPersonOpenedBy'], $f, $l);
                $notifier->SetRequestType('new');
                $notifier->Notify();
            } else {
                $notifier = new hs_notify($reqID, $reqHis, $req['xPerson'], $f, $l);
                $notifier->SetRequestType('existing');
                if (isset($req['request_events'])) {
                    $notifier->setLogEvents($req['request_events']);
                }
                // If reassigning then add a notification for the person who is being reassigned from
                if (! empty($req['reassignedfrom'])) {
                    $notifier->AddToNotifyQueue($req['reassignedfrom']);
                }
                $notifier->Notify();
            }
        }

        //Train SPAM filter. Only train when original req was an email and current action is closing request. Then just train initial req
        if ($req['fOpen'] == 0 && $req['mode'] != 'add') {
            //get request
            $spamreq = apiGetRequest($req['xRequest']);
            $spamreqhis = apiGetInitialRequest($req['xRequest']);

            if ($spamreq['fOpenedVia'] == 7) { //A portal request so train

                $text['body'] = stripFormBlurbs($spamreqhis['tNote']);
                $text['headers'] = $spamreq['sFirstName'].' '.$spamreq['sLastName'].' '.$spamreq['sEmail'];

                //train as spam
                $filter = new UserScape_Bayesian_Classifier($text, 'request');
                $filter->Train('0');
            } elseif ($spamreq['xOpenedViaId'] != 0) {
                $headers = hs_unserialize($spamreqhis['tEmailHeaders']);
                //train as not spam
                $text['subject'] = $spamreq['sTitle'];
                $text['from'] = $spamreq['sFirstName'].' '.$spamreq['sLastName'];
                $text['body'] = $spamreqhis['tNote'];

                $spam_reply = hs_parse_email_header((isset($headers['reply-to']) ? $headers['reply-to'] : ''));	//get reply-to header
                $spam_from = hs_parse_email_header((isset($headers['from']) ? $headers['from'] : ''));	//get from header
                $text['headers'] = $spam_reply['mailbox'].' '.$spam_reply['host'].' ';
                $text['headers'] .= $spam_from['mailbox'].' '.$spam_from['host'];

                $filter = new UserScape_Bayesian_Classifier($text);

                //train as not spam
                $filter->Train('0');
            }
        }

        // todo: Potentially xRequestHistory may point to a tLog history items instead one that has a tNote (If I'm reading the above code correctly)
        return ['xRequest'=>$reqID, 'sRequestPassword'=>$reqPassword, 'xRequestHistory'=>$reqHis, 'xPersonAssignedTo'=>$req['xPersonAssignedTo'], 'reassignedfrom'=>$req['reassignedfrom']];
    } else {
        return false;
    }
}

/******************************************
PROCESS DATA SENT FROM FORM/WS INTO A REQUEST
******************************************/
function apiProcessRequest($reqid, &$fm, &$files, $f, $l)
{
    global $user;

    $resfb = '';
    $reqHis = '';
    $assigned_user_id = $fm['xPersonAssignedTo']; //used for email headers which used assigned to name

    //Trim the email first so the mailer class doesn't complain and for validation
    $fm['sEmail'] = trim($fm['sEmail']);

    /*****************************************
    ERROR CHECKING
    *****************************************/
    $formerrors = [];
    //check that if closing then status cannot be active
    if ((! empty($fm['sub_updatenclose']) || ! empty($fm['sub_create_close'])) && $fm['xStatus'] == hs_setting('cHD_STATUS_ACTIVE', 1)) {
        $formerrors['xStatus'] = '';
        $formerrors['errorBoxText'] = lg_request_er_closewhileactive;
    }

    //a category must be selected
    if (empty($fm['xCategory']) && $fm['xStatus'] != hs_setting('cHD_STATUS_SPAM', 2) && $fm['fOpenedVia'] != 6 && ! isset($fm['ignore_category'])) {
        $formerrors['xCategory'] = '';
        $formerrors['errorBoxText'] = lg_request_er_nocategory;
    }

    //a contact method must be selected
    if (empty($fm['fOpenedVia']) && ! empty($fm['sub_create'])) {
        $formerrors['fOpenedVia'] = '';
        $formerrors['errorBoxText'] = lg_request_er_nocontactvia;
    }

    if (! hs_empty($fm['sEmail']) and ! filter_var($fm['sEmail'], FILTER_VALIDATE_EMAIL)) {
        $formerrors['errorBoxText'] = $fm['sEmail'].' '.lg_request_not_a_valid_email;
    }

    //Some customer information must be included
    if (hs_empty($fm['sUserId']) && hs_empty($fm['sFirstName']) && hs_empty($fm['sLastName']) && hs_empty($fm['sEmail']) && hs_empty($fm['sPhone'])) {
        $formerrors['errorBoxText'] = lg_request_er_nocustinfo;
    }

    //can't add a document without a note
    if (empty($fm['tBody']) && ! empty($files['doc']) && ! empty($fm['reattach'])) {
        $formerrors['tBody'] = '';
        $formerrors['errorBoxText'] = lg_request_er_nonotewfile;
    }

    //can't create a request without a note
    if (empty($fm['tBody']) && ! empty($fm['sub_create'])) {
        $formerrors['tBody'] = '';
        $formerrors['errorBoxText'] = lg_request_er_nonotecreate;
    }

    if (! isset($fm['batch_type']) && (! isset($fm['skipCustomChecks']) || $fm['skipCustomChecks'] == 0)) {
        //Get all custom fiels for this category
        $customs_for_cat = apiGetCategoryCustomFields($fm['xCategory']);

        foreach ($GLOBALS['customFields'] as $v) {
            $custid = 'Custom'.$v['fieldID'];

            //Empty custom fields which are no longer valid for this category if the category has changed
            if (! $v['isAlwaysVisible'] && ! in_array($v['fieldID'], $customs_for_cat)) {
                $fm[$custid] = '';
            }

            //Required fields
            $required = $v['isRequired'] == 1 && ($v['isAlwaysVisible'] || in_array($v['fieldID'], $customs_for_cat)) ? true : false;

            //Make sure required is only applied to public CF's when in portal (and public api)
            if (IN_PORTAL && $v['isPublic'] == 0) {
                $required = false;
            }

            if ($required && empty($fm[$custid]) && $fm['xStatus'] != hs_setting('cHD_STATUS_SPAM', 2)) {
                //Numeric fields shouldn't fail when 0, since 0 is a valid number
                if ($v['fieldType'] == 'numtext' && $fm[$custid] == 0) {
                    continue;
                }
                if ($v['fieldType'] == 'decimal' && $fm[$custid] == 0) {
                    continue;
                }
                if ($v['fieldType'] == 'text' && $fm[$custid] == 0) {
                    continue;
                }

                $formerrors[$custid] = '';
                $formerrors['errorBoxText'] = lg_request_er_customempty.' '.$v['fieldName'];
            }
        }
    }

    //If no errors then continue
    if (empty($formerrors)) {

        //Meta information on public and external requests
        $customeremail = $fm['fPublic'] == 1 && ! hs_empty($fm['tBody']) && ! empty($fm['emailfrom']) && ! hs_empty($fm['sEmail']) ? $fm['sEmail'] : '';
        if (! empty($fm['ccstaff']) || ! empty($fm['emailccgroup']) || ! empty($fm['emailbccgroup']) || ! empty($fm['emailtogroup']) || ! empty($fm['sTitle']) || ! empty($customeremail)) {
            $fm['tLog'] = hs_serialize(['ccstaff'=>$fm['ccstaff'], 'emailccgroup'=>$fm['emailccgroup'], 'emailbccgroup'=>$fm['emailbccgroup'], 'emailtogroup'=>$fm['emailtogroup'], 'sTitle'=>$fm['sTitle'], 'customeremail'=>$customeremail]);
        } else {
            $fm['tLog'] = '';
        }

        //Used in content id generation below
        $hostname = parse_url(cHOST, PHP_URL_HOST);

        //Adjust files array for inline images
        if (! empty($files['doc'])) {
            // Get all inline images and then adjust the numbers to match what is in file doc array
            // This is for textarea/markdown inline images
            preg_match_all('/##'.lg_inline_image.'(\s+)\((.*)\)##/', $fm['tBody'], $inlines);

            // Prefill $inline_replace to make sure all are replaced even if for some reason file isn't inserted
            $inline_replace = [];
            foreach ($inlines[0] as $string) {
                $inline_replace[$string] = '';
            }

            if (hs_setting('cHD_HTMLEMAILS')) { //can only do inline on html emails
                foreach ($files['doc']['error'] as $key => $error) {
                    if (in_array($files['doc']['type'][$key], $GLOBALS['imageMimeTypes'])) {
                        $loc = array_search($files['doc']['name'][$key], $inlines[2]);
                        if ($loc !== false && ! empty($files['doc']['name'][$key])) {
                            if ($error == UPLOAD_ERR_OK) {
                                $cid = md5(cHOST.$files['doc']['name'][$key].time()).'@'.$hostname;
                                $files['doc']['content-id'][$key] = $cid;
                                $files['doc']['is_inline'][$key] = true;
                                $inline_replace[$inlines[0][$loc]] = '<img src="cid:'.$cid.'" />';
                            }
                        }
                    }
                }
            }

            //If not empty then do replacements
            if (! empty($inline_replace)) {
                $fm['tBody'] = tokenReplace($fm['tBody'], $inline_replace);
            }
        }

        if (! empty($fm['attachment'])) {
            $attachCount = 10000;
            foreach ($fm['attachment'] as $k=>$id) {
                $document = apiGetDocument($id);
                // attach the document to the files array.
                $files['doc']['error'][$id] = 0;
                $files['doc']['name'][$id] = $document['sFilename'];
                $files['doc']['type'][$id] = $document['sFileMimeType'];
                $files['doc']['mimetype'][$id] = $document['sFileMimeType'];
                $files['doc']['tmp_name'][$id] = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$document['sFileLocation'];
                $files['doc']['size'][$id] = '';
                $files['doc']['xDocumentId'][$id] = $id;
                $files['doc']['content-id'][$id] = $document['sCID'];
                $files['doc']['is_inline'][$id] = true;
                $files['doc']['is_reattach'][$attachCount] = true;
                $attachCount++;
            }
        }

        //Reattachments- Get file info and then save to upload tmp location and allow attachment as normal
        $rct = 1000;
        if (! empty($fm['reattach'])) {
            foreach ($fm['reattach'] as $k=>$reattach_id) {
                //Get file info
                $reattach_file = $GLOBALS['DB']->GetRow('SELECT sFilename,sFileMimeType,sCID,blobFile,sFileLocation FROM HS_Documents WHERE HS_Documents.xDocumentId = ?', [$reattach_id]);
                //if file location is db we have it already and save to disk, if not just copy to upload location
                $tmpdir = is_writable(ini_get('upload_tmp_dir')) ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
                $destination = $tmpdir.'/'.md5($reattach_file['sFilename'].uniqid('helpspot').'.txt');

                // if the CID does not match what SwiftMail expects then force it
                // when attachments come from email clients they can be UUID or
                // other formats. This ensures it'll pass swiftmail validation
                if (! preg_match('/@/', $reattach_file['sCID'])) {
                    $hostname = parse_url(cHOST, PHP_URL_HOST);
                    $reattach_file['sCID'] = \Illuminate\Support\Str::random(10).'@'.$hostname;
                }

                // Replace the body and for every br force a newline. See #1325
                $fm['tBody'] = str_replace('<br />', "<br />\n", $fm['tBody']);
                $fm['tBody'] = inlineImageReplacer($fm['tBody']);

                if (Illuminate\Support\Str::contains($fm['tBody'], '##'.lg_inline_image)) {
                    $fm['tBody'] = preg_replace('/##'.lg_inline_image.'(\s+)\('.base64_encode($reattach_file['sFilename']).'\)##/', '<img src="cid:'.$reattach_file['sCID'].'" />', $fm['tBody']);
                    $fm['tBody'] = preg_replace('/##'.lg_inline_image.'(\s+)\('.$reattach_file['sFilename'].'\)##/', '<img src="cid:'.$reattach_file['sCID'].'" />', $fm['tBody']);
                }

                if (! hs_empty($reattach_file['sFileLocation'])) {
                    $reattachFilePath = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$reattach_file['sFileLocation'];

                    // If starts with S3, we need to get it from S3 to copy it to a new file
                    if (strpos($reattach_file['sFileLocation'], 's3://') !== false) {
                        $file = (new \HS\Domain\Documents\S3File($reattach_file['sFileLocation']))->toSpl();
                        $reattachFilePath = $file->getPathname();
                    }

                    $ok = copy($reattachFilePath, $destination);
                } else {
                    $ok = writeFile($destination, $reattach_file['blobFile']);
                }

                //Add other file meta info
                if ($ok) {
                    $files['doc']['error'][$rct] = 0;
                    $files['doc']['name'][$rct] = $reattach_file['sFilename'];
                    $files['doc']['type'][$rct] = $reattach_file['sFileMimeType'];
                    $files['doc']['tmp_name'][$rct] = $destination;
                    $files['doc']['size'][$rct] = '';
                    $files['doc']['is_reattach'][$rct] = true;
                    $files['doc']['content-id'][$rct] = $reattach_file['sCID'];
                    $files['doc']['is_inline'][$rct] = in_array($reattach_file['sFileMimeType'], $GLOBALS['imageMimeTypes']);
                }

                $rct++;
            }
        }

        // Replace drag/drop images
        $images = getWysiwgyImageUploadIds($fm['tBody']);
        $fm['tBody'] = wysiwygImageReplace($fm['tBody']);

        // Now add to the files array
        foreach ($images as $id) {
            // grab the document by id
            $document = apiGetDocument($id);
            // attach the document to the files array.
            $files['doc']['error'][$id] = 0;
            $files['doc']['name'][$id] = $document['sFilename'];
            $files['doc']['type'][$id] = $document['sFileMimeType'];
            $files['doc']['mimetype'][$id] = $document['sFileMimeType'];
            $files['doc']['tmp_name'][$id] = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$document['sFileLocation'];
            $files['doc']['size'][$id] = '';
            $files['doc']['xDocumentId'][$id] = $id;
            $files['doc']['content-id'][$id] = $document['sCID'];
            $files['doc']['is_inline'][$id] = true;
            $files['doc']['is_reattach'][$rct] = true;
            // Now do the real replacement in the body
            $fm['tBody'] = str_replace('cid:'.$id, 'cid:'.$document['sCID'], $fm['tBody']);
        }

        //Setup the message body. Do placeholder replacements so message is stored complete
        if (! hs_empty($fm['tBody'])) {

            //Create array of data for replacing placeholders
            $data = array_merge($fm, [
                'xRequest' => $reqid,
                'sRequestPassword' => (! hs_empty($fm['sRequestPassword']) ? $fm['sRequestPassword'] : ''),
                'fOpen' => (! empty($fm['sub_updatenclose']) ? 0 : 1),
            ]);

            //Get opened date if needed
            if (! empty($reqid) && utf8_strpos($fm['tBody'], '$date_opened') !== false) {
                $opened_check = $GLOBALS['DB']->GetOne('SELECT dtGMTOpened FROM HS_Request WHERE xRequest = ?', [$reqid]);
                $data['dtGMTOpened'] = $opened_check;
            }

            //Convert to markdown if necessary
            if ($fm['note_is_markdown']) {
                $fm['original_markdown_text'] = $fm['tBody']. "\n" . hs_user_signature(true); //Save original markdown text to use as text part of email, append signature
                $fm['tBody'] = hs_markdown($fm['tBody']);
            }

            //Append signature
            if (($fm['fPublic'] == 1 || $fm['external_note'] == 1) && $fm['xPersonOpenedBy'] != 0) {
                if (! hs_empty($user) and $user['xPerson'] != 0) {
                    $fm['tBody'] .= hs_user_signature();
                }
            }

            // Do replacement for message so history appears correctly
            $tBodyTemplateName = uniqid().'_message_tbody';
            \HS\View\Mail\TemplateTemporaryFile::create($tBodyTemplateName, $fm['tBody']);
            $fm['tBody'] = str_replace('@{{', '@@{{', $fm['tBody']);
            $fm['tBody'] = (string)restrictedView($tBodyTemplateName, getPlaceholders([], $data));
        }

        /************ ADD *************/
        if (! empty($fm['sub_create']) || ! empty($fm['sub_create_close'])) {
            $resfb = 1;
            $fm['mode'] = 'add';

            //close on create and close
            if (! empty($fm['sub_create_close'])) {
                $fm['fOpen'] = 0;
            }

            //handle send from
            if (! empty($fm['emailfrom']) && utf8_strpos($fm['emailfrom'], '*') !== false) {
                $em = explode('*', $fm['emailfrom']);
                $fm['xMailboxToSendFrom'] = $em[2];
            }

            //add the request
            $reqResult = apiAddEditRequest($fm, 1, $f, $l);
            if (is_array($reqResult)) {
                //set id's for staff notifications
                $reqHis = $reqResult['xRequestHistory'];
                $fm['xRequest'] = $reqResult['xRequest'];
                $fm['xRequestHistory'] = $reqResult['xRequestHistory'];
                $fm['sRequestPassword'] = $reqResult['sRequestPassword'];
                //Update assigned to
                $assigned_user_id = $reqResult['xPersonAssignedTo'];
            } else {
                $resfb = 2;
            }

            //Add time if sent
            if (isset($fm['tracker']) && parseTimeToSeconds($fm['tracker']['tTime']) > 0) {
                $tv = $fm['tracker'];
                $tv['xRequest'] = $reqResult['xRequest'];
                $timeID = apiAddTime($tv);
            }
        }

        /************ UPDATE *************/
        if ((! empty($fm['sub_update']) || ! empty($fm['sub_updatenclose'])) && ! empty($reqid)) {
            $resfb = 5;
            if (! empty($fm['sub_updatenclose'])) {
                $fm['fOpen'] = 0;
                $resfb = 3;
            }

            //If spam then set assignee back to inbox and redirect user to Q
            if ($fm['xStatus'] == hs_setting('cHD_STATUS_SPAM', 2)) {
                $fm['xPersonAssignedTo'] = 0;
                if (! empty($fm['sub_updatenclose'])) {	//If updating and closing, keep the spam open so it doesn't get trained.
                    $fm['fOpen'] = 1;
                    $resfb = 12;
                }
            }

            //Handle update of send from
            if (! empty($fm['emailfrom']) && utf8_strpos($fm['emailfrom'], '*') !== false) {
                $em = explode('*', $fm['emailfrom']);
                $fm['xMailboxToSendFrom'] = $em[2];
            } elseif ($fm['fPublic'] == 0) { //private notes we want to keep whatever xMailboxToSendFrom is currently set at in this request
                $orig_request = apiGetRequest($reqid);
                $fm['xMailboxToSendFrom'] = $orig_request['xMailboxToSendFrom'];
            }

            //add note if one sent. This is before triggers so it has access to the latest note.
            if (! hs_empty($fm['tBody'])) {
                $reqHis = apiAddRequestHistory([
                    'xRequest' => $reqid,
                    'xPerson' => $user['xPerson'],
                    'dtGMTChange' => $fm['dtGMTOpened'],
                    'fPublic' => $fm['fPublic'],
                    'iTimerSeconds' => $fm['iTimerSeconds'],
                    'tLog' => $fm['tLog'],
                    'tNote' => $fm['tBody'],
                    'fNoteIsHTML' => $fm['fNoteIsHTML'],
                ]);
                $fm['xRequestHistory'] = $reqHis;

                app('events')->flush('request.history.create');
            }

            //add a history event(s) - note object is used below in triggers
            $update = new requestUpdate($reqid, $fm, $user['xPerson'], $f, $l);
            $update->notify = false; //notify below instead
            $reqResult = $update->checkChanges();

            if (isset($reqResult['xPersonAssignedTo'])) {
                //Update assigned to
                $assigned_user_id = $reqResult['xPersonAssignedTo'];
            }

            //set id's for staff notifications + documents
            $fm['xRequest'] = $reqid;
            $fm['sRequestPassword'] = isset($reqResult['sRequestPassword']) ? $reqResult['sRequestPassword'] : apiGetRequestPassword($reqid);

            //Send notification from here instead of from within addreqhis so that we can send log and body in one email
            if (isset($fm['xRequestHistory']) || isset($reqResult['xRequestHistory'])) {
                //Array if both set else just the ID
                if (isset($fm['xRequestHistory']) && isset($reqResult['xRequestHistory'])) {
                    $ids = [$fm['xRequestHistory'], $reqResult['xRequestHistory']];	//first is body, second is log
                } else {
                    $ids = isset($fm['xRequestHistory']) ? $fm['xRequestHistory'] : $reqResult['xRequestHistory'];
                }

                $notifier = new hs_notify($reqid, $ids, $user['xPerson'], $f, $l);
                $notifier->SetRequestType('existing');

                // do we have any event logs?
                if ($update->logs) {
                    $logEvents = $update->logs->reduce(function ($carry, $item) {
                        if (! empty($item->sDescription)) {
                            return $carry."\n".$item->sDescription;
                        }
                    }, "\n");
                    $notifier->setLogEvents($logEvents);
                }

                // If reassigning then add a notification for the person who is being reassigned from
                if (! empty($reqResult['reassignedfrom'])) {
                    $notifier->AddToNotifyQueue($reqResult['reassignedfrom']);
                }
            }

            //Delete any drafts for this user when it's not a close and there is a note
            if ($fm['fOpen'] != 0 && ! hs_empty($fm['tBody'])) {
                apiDeleteRequestPersonDrafts($reqid, $user['xPerson']);
            }
        }

        /************ ADD DOCUMENT *************/
        //$files array uses format of $_FILES
        if (! empty($files['doc']) && isset($fm['xRequest'])) {
            foreach ($files['doc']['error'] as $key => $error) {
                if (! empty($files['doc']['name'][$key])) {
                    if ($error == UPLOAD_ERR_OK) {
                        if (isset($files['doc']['xDocumentId'][$key]) && ! empty($files['doc']['xDocumentId'][$key])) {
                            // Document already recorded in the database, so we'll set the request history ID
                            $GLOBALS['DB']->Execute('UPDATE HS_Documents SET xRequestHistory=? WHERE xDocumentId=?',
                                [$reqHis, $files['doc']['xDocumentId'][$key]]);
                        } else {
                            // The document does not yet exist in the database, so we create it and then add it to the request history
                            // Adding to the request history happens within apiAddDocument()
                            $docId = apiAddDocument(
                                $fm['xRequest'],
                                [
                                    [
                                        'name'=>$files['doc']['name'][$key],
                                        'mimetype'=>$files['doc']['type'][$key],
                                        'content-id'=>$files['doc']['content-id'][$key],
                                        'body'=>file_get_contents($files['doc']['tmp_name'][$key]),
                                    ]
                                ],
                                $reqHis,
                                __FILE__, __LINE__);
                            $files['doc']['xDocumentId'] = [$key => $docId];
                        }
                    } else {
                        errorLog(hs_imageerror($error), 'Document Upload', $f, $l);
                    }
                }
            }
        }

        // now really do all the notifications
        if (isset($notifier)) {
            $notifier->Notify();
        }

        # NEW MAILER
        $emailAttachments = \HS\Mail\Attachments::parse( ($files['doc'] ?? []) )->persist();

        /************ ADD NOTIFICATIONS WHEN USING CC STAFF FEATURE*************/
        if (! empty($fm['ccstaff']) && isset($fm['xRequest']) && ! hs_empty($fm['tBody'])) {
            $cclist = explode(',', $fm['ccstaff']);

            # NEW MAILER
            foreach ($cclist as $v) {

                //If they've selected to subscribe the users then do so
                if (isset($fm['subscribe_all_ccstaff']) && $fm['subscribe_all_ccstaff'] == 1) {
                    apiSubscribeToRequest($fm['xRequest'], $v);
                }

                $ccperson = apiGetUser($v);

                $vars = getPlaceholders([
                    'email_subject' => lg_mailsub_cc,
                    'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$fm['xRequest'].'}',
                    'name' => $user['sFname'].' '.$user['sLname'],
                    'requestcheckurl' => cHOST.'/admin?pg=request&reqid='.$fm['xRequest'],
                    'requesturl' => cHOST.'/admin?pg=request&reqid='.$fm['xRequest'],
                ], $fm);

        ### ### # START HELPSPOT NEW MAILER
                $to = [$ccperson['sEmail']];
                if (trim($ccperson['fNotifyEmail2'])) {
                    $to[] = $ccperson['sEmail2'];
                }
                $message = (new \HS\Mail\Mailer\MessageBuilder(\HS\Mail\SendFrom::default(), $fm['xRequest']))
                    ->to($to)
                    ->subject('ccstaff', $vars)
                    ->body('ccstaff', $fm['tBody'], $vars);

                \HS\Jobs\SendMessage::dispatch($message, $emailAttachments)
                    ->onQueue(config('queue.high_priority_queue')); // mail.private
        ### ### # END HELPSPOT NEW MAILER

                if (! hs_empty($ccperson['sSMS']) && ($ccperson['fNotifySMS'] || ($ccperson['fNotifySMSUrgent'] && $fm['fUrgent']))) { //send sms if set to send all or if set to send urgent only
                    $sms = apiGetSMS($ccperson['xSMSService']);
                    $sms_msg = strip_tags($fm['tBody']);
                    if (! hs_empty($sms_msg)) {

                ### ### # START HELPSPOT NEW MAILER
                        $vars = getPlaceholders(['label' => ''], $fm);
                        $message = (new \HS\Mail\Mailer\MessageBuilder(\HS\Mail\SendFrom::default(), $fm['xRequest']))
                            ->to($ccperson['sSMS'].'@'.$sms['sAddress'])
                            ->body('sms', $sms_msg, $vars);

                        // For SMS we need to manually truncate message after it's passed through hs_body
                        $message->setBodyHtml(utf8_substr($message->getHtml(), 0, ($sms['sMsgSize'] - 10))) ;
                        $message->setBodyText(utf8_substr($message->getText(), 0, ($sms['sMsgSize'] - 10))) ;

                        \HS\Jobs\SendMessage::dispatch($message, $emailAttachments)
                            ->onQueue(config('queue.high_priority_queue')); // mail.private
                ### ### # END HELPSPOT NEW MAILER
                    }
                }
            }
        }

        /************ EMAIL CUSTOMER *************/
        //handle customer updates, send out email if all goes well
        // Send email for customer update
        if ($fm['fPublic'] == 1 && ! hs_empty($fm['tBody']) && isset($fm['xRequest']) && ! empty($fm['emailfrom']) && isset($fm['xRequestHistory'])) {
            if (validateEmail($fm['sEmail'])) {
                $ccs = explode(',', $fm['emailccgroup']);
                $bcs = explode(',', $fm['emailbccgroup']);

                //Build message body
                $vars = getPlaceholders([
                          'email_subject'=> $fm['sTitle'],
                          'tracking_id'=> '{'.trim(hs_setting('cHD_EMAILPREFIX')).$fm['xRequest'].'}',
                          'requestcheckurl'=> cHOST.'/index.php?pg=request.check&id='.$fm['xRequest'].$fm['sRequestPassword'],
                    ],
                    $fm
                );

        ### ### # START HELPSPOT NEW MAILER
                $sendFrom = \HS\Mail\SendFrom::fromRequestForm($fm['emailfrom'], $assigned_user_id);
                $message = (new \HS\Mail\Mailer\MessageBuilder($sendFrom, $fm['xRequest']))
                    ->to($fm['sEmail'])
                    ->cc($ccs)
                    ->bcc($bcs)
                    ->setType('public')
                    ->subject('public', $vars)
                    ->body('public', $fm['tBody'], $vars);

                \HS\Jobs\SendMessage::dispatch($message, $emailAttachments, $publicEmail=true)
                    ->onQueue(config('queue.high_priority_queue')); // mail.public
        ### ### # END HELPSPOT NEW MAILER
            }
        }

        /************ EMAIL EXTERNAL *************/
        //handle customer updates, send out email if all goes well
        // Send email for customer update
        if ($fm['fPublic'] == 0 && $fm['external_note'] == 1 && ! hs_empty($fm['tBody']) && isset($fm['xRequest']) && ! hs_empty($fm['emailfrom'])) {
            $tos = explode(',', $fm['emailtogroup']);
            $ccs = explode(',', $fm['emailccgroup']);
            $bcs = explode(',', $fm['emailbccgroup']);

            $vars = getPlaceholders([
                'email_subject' => $fm['sTitle'],
                'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$fm['xRequest'].'}',
                'requestcheckurl' => cHOST.'/index.php?pg=request.check&id='.$fm['xRequest'].$fm['sRequestPassword'], ], $fm);

    ### ### # START HELPSPOT NEW MAILER
            $sendFrom = \HS\Mail\SendFrom::fromRequestForm($fm['emailfrom'], $assigned_user_id);
            $message = (new \HS\Mail\Mailer\MessageBuilder($sendFrom, $fm['xRequest']))
                ->to($tos)
                ->cc($ccs)
                ->bcc($bcs)
                ->setType('public')
                ->subject('external', $vars)
                ->body('external', $fm['tBody'], $vars);

            \HS\Jobs\SendMessage::dispatch($message, $emailAttachments, $publicEmail=true)
                ->onQueue(config('queue.high_priority_queue')); // mail.public
    ### ### # END HELPSPOT NEW MAILER
        }

        //Clean up reattachments. This needs to be done below sending email so that they're still available for sent email
        if (! empty($files['doc']) && isset($fm['xRequest'])) {
            foreach ($files['doc']['error'] as $key => $error) {
                //Remove temporary file we needed for reattachment
                if ($files['doc']['is_reattach'][$key]) {
                    unlink($files['doc']['tmp_name'][$key]);
                }
            }
        }

        return ['fb'=>$resfb, 'reqid'=>$fm['xRequest'], 'sRequestPassword'=>$fm['sRequestPassword'], 'xRequestHistory'=>$reqHis];
    } else {	//end error check
        return $formerrors;
    }
}

/******************************************
ADD REQUEST HISTORY
******************************************/
function apiAddRequestHistory($req)
{
    // initialize
    $req['xRequest'] = hs_numeric($req, 'xRequest') ? $req['xRequest'] : 0;
    $req['xPerson'] = hs_numeric($req, 'xPerson') ? $req['xPerson'] : 0;
    $req['dtGMTChange'] = hs_numeric($req, 'dtGMTChange') ? $req['dtGMTChange'] : 0;
    $req['xDocumentId'] = hs_numeric($req, 'xDocumentId') ? $req['xDocumentId'] : 0;
    $req['fPublic'] = hs_numeric($req, 'fPublic') ? $req['fPublic'] : 0;
    $req['fInitial'] = hs_numeric($req, 'fInitial') ? $req['fInitial'] : 0;
    $req['iTimerSeconds'] = hs_numeric($req, 'iTimerSeconds') ? $req['iTimerSeconds'] : 0;
    $req['tLog'] = isset($req['tLog']) ? $req['tLog'] : '';
    $req['tNote'] = isset($req['tNote']) ? $req['tNote'] : '';
    $req['fNoteIsHTML'] = hs_numeric($req, 'fNoteIsHTML') ? $req['fNoteIsHTML'] : 0;
    $req['fNoteIsClean'] = isset($req['fNoteIsClean']) ? $req['fNoteIsClean'] : false; // If the note has already been through the HTML cleaner
    $req['tEmailHeaders'] = isset($req['tEmailHeaders']) ? $req['tEmailHeaders'] : '';
    $req['sRequestHistoryHash'] = isset($req['sRequestHistoryHash']) ? $req['sRequestHistoryHash'] : '';

    $newHisID = 0;

    // Trim to a maximum length to prevent loops from creating giant requests
    // This can truncate HTML so do before HTML cleaner so tags can be balanced
    $req['tNote'] = utf8_substr($req['tNote'], 0, 70000);

    if ($req['fNoteIsHTML'] && ! $req['fNoteIsClean']) {
        $clean_note = app('html.cleaner')->clean($req['tNote']);
    } else {
        // Non-HTML notes are escaped everywhere they're used. We need to do this because customers
        // want people to be able to submit HTML in the portal form for instance. If we clean
        // that then things like <form> elements would be stripped.
        $clean_note = $req['tNote'];
    }

    if ($req['xRequest'] != 0) {
        $GLOBALS['DB']->Execute('INSERT INTO HS_Request_History(xRequest,xPerson,dtGMTChange,fPublic,fInitial,iTimerSeconds,tLog,tNote,fNoteIsHTML,tEmailHeaders,sRequestHistoryHash)
                                            VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                                                [$req['xRequest'], $req['xPerson'], $req['dtGMTChange'],
                                                      $req['fPublic'], $req['fInitial'], $req['iTimerSeconds'], $req['tLog'],
                                                      $clean_note, $req['fNoteIsHTML'], $req['tEmailHeaders'], $req['sRequestHistoryHash'], ]);

        $newHisID = dbLastInsertID('HS_Request_History', 'xRequestHistory');

        //Update last reply by if a public note
        if ($req['fPublic'] == 1) {
            $GLOBALS['DB']->Execute('UPDATE HS_Request SET iLastReplyBy = ? WHERE xRequest = ?', [$req['xPerson'], $req['xRequest']]);
        }
    }

    // Get history id
    if (! isset($newHisID) || is_null($newHisID) || $newHisID == 0) {
        $newHisID = dbLastInsertID('HS_Request_History', 'xRequestHistory');
    }

    // If we should notify then do so
    /*
     * This is often run inside a transaction so this is queued.
     * This event should be flushed when transaction is complete (see instances of dispatcher::flush() above
     * Only fire this event if a new Note is added, instead of a log. We don't perform any actions on new log events
     */
    if (isset($req['tNote']) === true && empty($req['tNote']) === false) {
        app('events')->push('request.history.create', [$newHisID]);
    }

    return $newHisID;
}

function apiPinNote($xRequestHistory)
{
    $req = $GLOBALS['DB']->GetRow('SELECT fPinned FROM HS_Request_History WHERE xRequestHistory  = ?', [$xRequestHistory]);
    $pinned = ($req['fPinned'] == 1) ? 0 : 1;

    return $GLOBALS['DB']->Execute('UPDATE HS_Request_History SET fPinned = ? WHERE xRequestHistory = ?', [$pinned, $xRequestHistory]);
}

/**
 * Add an attachment.
 * @param $file
 * @return mixed
 */
function apiAddAttachment($file)
{
    if ($id = apiWriteFileToDisk($file)) {
        return $id;
    } else {
        return apiWriteFileToDb($file);
    }
}

function apiWriteFileToDisk($file)
{
    if (hs_setting('cHD_ATTACHMENT_LOCATION') != 'file') {
        return false;
    }
    $file_path = apiCreateDateFolder();
    //hashed file name to prevent "bad guys" from finding it easy should someone put their files in the web root path.
    //when possible use a real ext. this way files still viewable and avoid issues where backups are backing up binary files

    $mime = $file->getMimeType();
    $ext = hs_lookup_mime($mime);
    $extension = ($ext ? $ext : 'txt');
    $full_file_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$file_path;
    //Use uniqid() in hash to ensure it's unique
    $file_name = md5($file->getClientOriginalName().uniqid('helpspot')).'.'.$extension;

    // Try and write files to disk
    try {
        $file->move($full_file_path, $file_name);
    } catch (Exception $e) {
        \Illuminate\Support\Facades\Log::error($e);
        errorLog('Attachment could not be written to '.$full_file_path.', inserted in DB instead', 'File System', __FILE__, __LINE__);

        return false;
    }

    $hostname = parse_url(cHOST, PHP_URL_HOST);
    $sCID = md5(cHOST.$file_name.time()).'@'.$hostname;

    try {
        $GLOBALS['DB']->Execute('INSERT INTO HS_Documents(sFilename,sFileMimeType,sCID,sFileLocation) VALUES(?,?,?,?)',
            [$file->getClientOriginalName(), $mime, $sCID, $file_path.$file_name]);

        //Change perms so files are not executable
        hs_chmod($full_file_path.$file_name, 0666);

        $docid = dbLastInsertID('HS_Documents', 'xDocumentId');

        return $docid;
    } catch(\Exception $e) {
        \Illuminate\Support\Facades\Log::error($e);
        unlink($full_file_path); //clean up file if it was written but insert failed

        return false;
    }
}

function apiWriteFileToDb($file)
{
    $hostname = parse_url(cHOST, PHP_URL_HOST);
    $sCID = md5(cHOST.$file->getClientOriginalName().time()).'@'.$hostname;

    $GLOBALS['DB']->Execute('INSERT INTO HS_Documents(sFilename,sFileMimeType,sCID) VALUES(?,?,?)',
        [$file->getClientOriginalName(), $file->getMimeType(), $sCID]);

    $docid = dbLastInsertID('HS_Documents', 'xDocumentId');

    $GLOBALS['DB']->UpdateBlob('HS_Documents', 'blobFile', file_get_contents($file->getRealPath()), ' xDocumentId = '.$docid);

    return $docid;
}

function apiCreateDateFolder()
{
    $year = date('Y');
    $month = date('n');
    $day = date('j');
    $yr_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year;
    $mo_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year.'/'.$month;
    $dy_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year.'/'.$month.'/'.$day;
    if (! is_dir($dy_path)) {
        _apiMakeFolder($yr_path); //make year folder
        _apiMakeFolder($mo_path); //make month folder
        _apiMakeFolder($dy_path); //make day folder
    }

    return '/'.$year.'/'.$month.'/'.$day.'/';
}

function _apiMakeFolder($dir)
{
    if (! is_dir($dir)) {
        @mkdir($dir);
        hs_chmod($dir, 0777);
    }
}

/******************************************
ADD AN ATTACHMENT AS REQUEST HISTORY
******************************************/
function apiAddDocument($msgRequestId, $msgFiles, $reqHis, $f, $l)
{
    set_time_limit(120); //Increase time allotted for operation

    $docid = null;

    if ($msgRequestId) {
        if (! empty($msgFiles) && count($msgFiles) > 0) {
            foreach ($msgFiles as $file) {
                if (! isset($file['name'])) {
                    $file['name'] = 'unknown_filename';
                }
                $bodySize = strlen($file['body']);
                \Illuminate\Support\Facades\Log::debug('[api.requests.lib.php::apiAddDocument] preparing to save attachment', [
                    'filename' => $file['name'],
                    'content_id' => $file['content-id'],
                    'transaction' => $file['transaction'] ?? '',
                    'body_size_bytes' => $bodySize,
                ]);
                if (! empty($file['body'])) {
                    if ($bodySize < hs_setting('cHD_MAIL_MAXATTACHSIZE')) {
                        $file_write_worked = false;
                        $file_path = '';

                        $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

                        //SAVE ATTACHMENT TO FILE SYSTEM OR DB
                        if (hs_setting('cHD_ATTACHMENT_LOCATION') == 'file') {
                            $year = date('Y');
                            $month = date('n');
                            $day = date('j');
                            $yr_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year;
                            $mo_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year.'/'.$month;
                            $dy_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year.'/'.$month.'/'.$day;

                            // Create path to directory location if it doesn't exist
                            // Set writable perms (hs_chmod). Sometimes the dir is built by root when running under cron, other times by the Apache user if done via file upload. Esp when root builds it the system is then unable to
                            // write uploaded files to the dir's because the apache user doesn't have access.
                            if (! is_dir($dy_path)) {
                                if (! is_dir($yr_path)) {
                                    @mkdir($yr_path);
                                    hs_chmod($yr_path, 0777);
                                } //make year folder
                                if (! is_dir($mo_path)) {
                                    @mkdir($mo_path);
                                    hs_chmod($mo_path, 0777);
                                } //make month folder
                                @mkdir($dy_path);
                                hs_chmod($dy_path, 0777); 							//make day folder, Don't need is_dir check here since it's done first so we know it isn't
                            }

                            //hashed file name to prevent "bad guys" from finding it easy should someone put their files in the web root path.
                            $ext = hs_lookup_mime($file['mimetype']); //when possible use a real ext. this way files still viewable and avoid issues where backups are backing up binary files
                            $extension = ($ext ? $ext : 'txt');
                            //Use uniqid() in hash to ensure it's unique
                            $dir_path = '/'.$year.'/'.$month.'/'.$day;
                            $file_path = $dir_path.'/'.md5($file['name'].uniqid('helpspot')).'.'.$extension;
                            $full_file_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$file_path;

                            \Illuminate\Support\Facades\Log::debug('[api.requests.lib.php::apiAddDocument] checking file save path', [
                                'dir_path' => hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$dir_path,
                                'is_dir' => is_dir(hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$dir_path),
                                'file_to_be_saved' => $full_file_path,
                                'file_already_exists' => file_exists($full_file_path),
                                'transaction' => $file['transaction'] ?? '',
                            ]);

                            // Try and write files to disk
                            $file_write_worked = writeFile($full_file_path, $file['body']);

                            \Illuminate\Support\Facades\Log::debug('[api.requests.lib.php::apiAddDocument] attempted to write file to disk', [
                                'filename' => $file['name'],
                                'content_id' => $file['content-id'],
                                'transaction' => $file['transaction'] ?? '',
                                'write_successful' => $file_write_worked,
                            ]);

                            // Add document to document table
                            if ($file_write_worked) {
                                $sCID = (isset($file['content-id']) ? ltrim(rtrim($file['content-id'], '>'), '<') : '');

                                try {
                                    $GLOBALS['DB']->Execute('INSERT INTO HS_Documents(sFilename,sFileMimeType,sCID,sFileLocation,xRequestHistory) VALUES(?,?,?,?,?)',
                                        [$file['name'], $file['mimetype'], $sCID, $file_path, $reqHis]);
                                    $docid = dbLastInsertID('HS_Documents', 'xDocumentId');
                                    \Illuminate\Support\Facades\Log::debug('[api.requests.lib.php::apiAddDocument] file-storage document saved to HS_Documents', [
                                        'xDocumentId' => $docid,
                                        'content_id' => $file['content-id'],
                                        'transaction' => $file['transaction'] ?? '',
                                    ]);
                                    hs_chmod($full_file_path, 0666);
                                } catch(\Exception $e) {
                                    $file_write_worked = false;
                                    @unlink($full_file_path); //clean up file if it was written but insert failed
                                    \Illuminate\Support\Facades\Log::error($e);
                                }
                            } else { //log error since file path isn't writable
                                // This is written to inside of a transaction, so never succeeds, add file logging
                                \Illuminate\Support\Facades\Log::error('Attachment could not be written to '.$full_file_path.', attempting to insert into DB instead', [
                                    'filename' => $file['name'],
                                    'content_id' => $file['content-id'],
                                    'transaction' => $file['transaction'] ?? '',
                                ]);
                                errorLog('Attachment could not be written to '.$full_file_path.', attempting to insert into DB instead', 'File System', $f, $l);
                            }
                        }

                        //If we're using the DB do so, or if the file system write failed let's fail over to the DB
                        if (hs_setting('cHD_ATTACHMENT_LOCATION') == 'db' || ! $file_write_worked) {
                            // Add document to document table
                            $sCID = (isset($file['content-id']) ? ltrim(rtrim($file['content-id'], '>'), '<') : '');
                            $GLOBALS['DB']->Execute('INSERT INTO HS_Documents(sFilename,sFileMimeType,sCID,xRequestHistory) VALUES(?,?,?,?)',
                                                                                    [$file['name'], $file['mimetype'], $sCID, $reqHis]);

                            $docid = dbLastInsertID('HS_Documents', 'xDocumentId');
                            \Illuminate\Support\Facades\Log::debug('[api.requests.lib.php::apiAddDocument] database-storage document saved to HS_Documents', [
                                'xDocumentId' => $docid,
                                'content_id' => $file['content-id'],
                                'transaction' => $file['transaction'] ?? '',
                            ]);
                            $GLOBALS['DB']->UpdateBlob('HS_Documents', 'blobFile', $file['body'], ' xDocumentId = '.$docid);
                        }

                        $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
                    } else {
                        errorLog('Attachment too large. Request:'.$msgRequestId.' Size:'.$bodySize.' bytes', 'Email', $f, $l);
                    }
                } else {
                    \Illuminate\Support\Facades\Log::debug('[api.requests.lib.php::apiAddDocument] attachment body empty', [
                        'filename' => $file['name'],
                        'content_id' => $file['content-id'],
                        'transaction' => $file['transaction'] ?? '',
                    ]);
                }
            }
        }
    }

    return $docid;
}

/******************************************
Determine assigned user when using autoassign.
******************************************/
function apiAutoAssignStaff($current_assigned, $category, $f, $l)
{
    $id = $current_assigned;

    if ($category != 0) {
        $users = apiGetAllUsers();
        $users = rsToArray($users, 'xPerson', false);

        $cat = apiGetCategory($category, $f, $l);
        $staff = hs_unserialize($cat['sPersonList']);

        // Remove OOO users from staff list who are set to have their requests assigned to inbox
        // The break the RR/Random flows. By doing this here, the apiOutOfOffice() call which follows apiAutoAssignStaff()
        // will work correctly. Meaning, if this logic here says Bob should be assigned, but Bob's settings say to
        // forward to Sally that will work correctly. It also fixes the case where Bob has said to assign things
        // to the inbox by not allowing that for random/RR but instead going to someone else in those cases.
        foreach ($staff as $k=>$person) {
            if ($users[$person]['xPersonOutOfOffice'] == -1) {
                unset($staff[$k]);
            }
        }

        switch ($cat['fAutoAssignTo']) {
            case '0':
                $id = $current_assigned;

                break;

            //Assign to default
            case '1':
                $id = $cat['xPersonDefault'];

                break;

            //Assign random
            case '2':
                $k = array_rand($staff);
                $id = ! empty($staff) ? $staff[$k] : $current_assigned;

                break;

            //Assign random no admins
            case '3':
                $list = [];

                //Remove admins
                foreach ($staff as $k=>$v) {
                    if ($users[$v]['fUserType'] != 1) {
                        $list[] = $v;
                    }
                }

                $k = array_rand($list);
                $id = ! empty($list) ? $list[$k] : $current_assigned;

                break;

            //Assign by least reqs
            case '4':
                $xPerson = $GLOBALS['DB']->GetOne('SELECT xPerson, count(xRequest) as openreqs
												   FROM HS_Person LEFT OUTER JOIN HS_Request ON HS_Person.xPerson = HS_Request.xPersonAssignedTo AND HS_Request.fOpen = 1
												   WHERE HS_Person.fDeleted = 0 AND HS_Person.xPersonOutOfOffice = 0
												   		 	AND HS_Person.xPerson IN ('.implode(',', $staff).')
                                                   GROUP BY xPerson ORDER BY openreqs ASC');

                $id = ! empty($xPerson) ? $xPerson : 0;

                break;

            //Assign by least reqs no admins
            case '5':
                $xPerson = $GLOBALS['DB']->GetOne('SELECT xPerson, count(xRequest) as openreqs
												   FROM HS_Person LEFT OUTER JOIN HS_Request ON HS_Person.xPerson = HS_Request.xPersonAssignedTo AND HS_Request.fOpen = 1
												   WHERE HS_Person.fDeleted = 0 AND HS_Person.xPersonOutOfOffice = 0 AND fUserType <> 1
												   		 	AND HS_Person.xPerson IN ('.implode(',', $staff).')
												   GROUP BY xPerson ORDER BY openreqs ASC');
                $id = ! empty($xPerson) ? $xPerson : 0;

                break;

            //Round Robin
            case '7': //no admin mode
                //NOTE: case 7 falls through to case 6. This just creates a new staff list that has no admins in it for that setting.
                $newstaff = [];

                //Remove staff that are admins
                foreach ($staff as $k=>$v) {
                    if ($users[$v]['fUserType'] != 1) {
                        $newstaff[] = $staff[$k];
                    }
                }

                //Remove staff that are out of office

                //Set new staff list
                $staff = $newstaff;

                //If we emptied array add back in one
                if (empty($staff)) {
                    $staff[0] = 0;
                }

            case '6':
                //We need to get the RR number every time this runs not just once per HTTP request otherwise if you're pulling in email and get 5 at once they all go to the same person
                $rr_data = $GLOBALS['DB']->GetOne('SELECT tValue FROM HS_Settings WHERE sSetting = ?', ['cHD_ROUNDROBIN']);
                $rr = hs_empty($rr_data) ? [] : hs_unserialize($rr_data);

                if (! isset($rr[$category])) {
                    //No previous history for cat so set it up as first person in cat
                    $rr[$category] = $staff[0];

                    $id = $rr[$category];
                } else {
                    // Re-key available staff array to help
                    // calculate next available staffer
                    $available_staff = array_values($staff);

                    //We know the previous person so move to the next
                    $key = array_search($rr[$category], $available_staff);

                    //Check if we're at the end of the array, if so move back to the start
                    $rr[$category] = isset($available_staff[$key + 1]) ? $available_staff[$key + 1] : $available_staff[0];

                    $id = $rr[$category];
                }

                //Save history for next run
                storeGlobalVar('cHD_ROUNDROBIN', hs_serialize($rr));

                break;
            default:
                break;
        }
    }

    return $id;
}

/******************************************
 * Check out of office , if out reassign
 *****************************************
 * @param $id
 * @return int|mixed|string
 */
function apiOutOfOffice($id)
{
    $id = is_numeric($id) ? $id : 0;

    if ($id != 0) {
        //Do out of office checks
        $assignedPerson = apiGetUser($id);
        if (is_array($assignedPerson)) {
            if ($assignedPerson['xPersonOutOfOffice'] != 0 && $assignedPerson['xPersonOutOfOffice'] != -1) {
                $id = $assignedPerson['xPersonOutOfOffice'];
            } elseif ($assignedPerson['xPersonOutOfOffice'] == -1) {
                //Assign to inbox
                $id = 0;
            }
        }
    }

    return $id;
}

/******************************************
CONFIRM USER PERMISSIONS
******************************************/
function apiCurrentUserCanAccessRequest($reqid)
{
    global $user;

    if(empty($reqid) || $reqid == 0) return false;

    $req = apiGetRequest($reqid);

    if (perm('fCanViewOwnReqsOnly')) {
        if ($req['xPersonAssignedTo'] != $user['xPerson']) {
            return false;
        }
    }

    if (perm('fLimitedToAssignedCats')) {
        $cats = apiGetUserCats($user['xPerson']);
        if (! in_array($req['xCategory'], $cats)) {
            return false;
        }
    }

    return true;
}

/******************************************
 * Update the count for last time read by assigned user
 *****************************************
 * @param $reqid
 * @return bool
 */
function updateReadUnread($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    $hiscount = $GLOBALS['DB']->GetRow('SELECT COUNT(*) as hiscount FROM HS_Request_History WHERE xRequest = ?', [$reqid]);
    $GLOBALS['DB']->Execute('UPDATE HS_Request SET iLastReadCount = ? WHERE xRequest = ?', [$hiscount['hiscount'], $reqid]);
    return true;
}

/******************************************
 * DELETE A REQUEST
 *****************************************
 * @param $reqid
 */
function apiDeleteRequest($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    //Delete in order of least important data loss first!

    //NOTE UPDATE apiMergeRequests() FOR ANY TABLES ADDED HERE

    $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

    //Delete pushed request information
    $GLOBALS['DB']->Execute('DELETE FROM HS_Request_Pushed WHERE xRequest = ?', [$reqid]);

    //Delete saved note drafts
    $GLOBALS['DB']->Execute('DELETE FROM HS_Request_Note_Drafts WHERE xRequest = ?', [$reqid]);

    //Delete merge table information
    $GLOBALS['DB']->Execute('DELETE FROM HS_Request_Merged WHERE xRequest = ?', [$reqid]);

    //Delete subscriptions
    $GLOBALS['DB']->Execute('DELETE FROM HS_Subscriptions WHERE xRequest = ?', [$reqid]);

    //Delete assignment chain
    $GLOBALS['DB']->Execute('DELETE FROM HS_Assignment_Chain WHERE xRequest = ?', [$reqid]);

    //Delete reminders
    $reminders = $GLOBALS['DB']->Execute('SELECT HS_Reminder.* FROM HS_Reminder WHERE xRequest = ?', [$reqid]);

    if (hs_rscheck($reminders)) {
        while ($rems = $reminders->FetchRow()) {
            apiDeleteReminder($rems['xReminder']);
        }
    }

    //Delete rep tags
    $GLOBALS['DB']->Execute('DELETE FROM HS_Request_ReportingTags WHERE xRequest = ?', [$reqid]);

    //Delete req history and documents
    $reqhis = $GLOBALS['DB']->Execute('SELECT HS_Request_History.*, HS_Documents.xDocumentId, HS_Documents.sFilename, HS_Documents.sFileMimeType, HS_Documents.sFileLocation
								     FROM HS_Request_History
								     	LEFT OUTER JOIN HS_Documents ON HS_Request_History.xRequestHistory = HS_Documents.xRequestHistory
								     WHERE xRequest = ?
								     ORDER BY HS_Request_History.dtGMTChange DESC, HS_Request_History.xRequestHistory DESC', [$reqid]);
    if (hs_rscheck($reqhis)) {
        while ($rh = $reqhis->FetchRow()) {
            //Delete disk based attachments if present
            if (! hs_empty($rh['sFileLocation']) && ! hs_empty($rh['xDocumentId'])) {
                unlink(hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$rh['sFileLocation']);
            }
            if (! hs_empty($rh['xDocumentId'])) {
                $GLOBALS['DB']->Execute('DELETE FROM HS_Documents WHERE xDocumentId = ?', [$rh['xDocumentId']]);
            }
        }
    }

    $GLOBALS['DB']->Execute('DELETE FROM HS_Request_History WHERE xRequest = ?', [$reqid]);

    //Delete time tracker
    $GLOBALS['DB']->Execute('DELETE FROM HS_Time_Tracker WHERE xRequest = ?', [$reqid]);

    //Delete request
    $GLOBALS['DB']->Execute('DELETE FROM HS_Request WHERE xRequest = ?', [$reqid]);

    $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/

    event('request.delete', [$reqid]);
}

/******************************************
 * ADD/EDIT FILTER
 *****************************************
 * @param $fil
 * @return bool
 */
function apiAddEditFilter($fil)
{
    $fil['mode'] = isset($fil['mode']) ? $fil['mode'] : 'add';
    $fil['xFilter'] = hs_numeric($fil, 'xFilter') ? $fil['xFilter'] : 0;
    // initialize
    $fil['xPerson'] = hs_numeric($fil, 'xPerson') ? $fil['xPerson'] : 0;
    $fil['fGlobal'] = hs_numeric($fil, 'fGlobal') ? $fil['fGlobal'] : 0;
    $fil['fShowCount'] = hs_numeric($fil, 'fShowCount') ? $fil['fShowCount'] : 0;
    $fil['fCustomerFriendlyRSS'] = hs_numeric($fil, 'fCustomerFriendlyRSS') ? $fil['fCustomerFriendlyRSS'] : 0;
    $fil['sShortcut'] = isset($fil['sShortcut']) ? $fil['sShortcut'] : '';
    $fil['sFilterName'] = isset($fil['sFilterName']) ? $fil['sFilterName'] : '';
    $fil['tFilterDef'] = isset($fil['tFilterDef']) ? $fil['tFilterDef'] : '';
    $fil['fCacheNever'] = hs_numeric($fil, 'fCacheNever') ? $fil['fCacheNever'] : 0;
    $fil['fDisplayTop'] = hs_numeric($fil, 'fDisplayTop') ? $fil['fDisplayTop'] : 0;
    $fil['fType'] = hs_numeric($fil, 'fType') ? $fil['fType'] : 2;
    $fil['sFilterView'] = isset($fil['sFilterView']) ? $fil['sFilterView'] : 'grid';
    //	$fil['fPermissionGroup'] = hs_numeric($fil,'fPermissionGroup') ? $fil['fPermissionGroup'] : 0;
    $fil['sPersonList'] = isset($fil['sPersonList']) ? $fil['sPersonList'] : [];

    //Make sure perm group is 0
    //	if($fil['fType'] != 3) $fil['fPermissionGroup'] = 0;

    if ($fil['mode'] == 'add') {
        if ($fil['xPerson'] > 0) {
            $res = $GLOBALS['DB']->Execute('INSERT INTO HS_Filters (xPerson,fShowCount,fCustomerFriendlyRSS,sFilterName,tFilterDef,sShortcut,fCacheNever,fDisplayTop,fType,sFilterView,iCachedMinutes) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$fil['xPerson'], $fil['fShowCount'], $fil['fCustomerFriendlyRSS'], $fil['sFilterName'], $fil['tFilterDef'], $fil['sShortcut'], $fil['fCacheNever'], $fil['fDisplayTop'], $fil['fType'], $fil['sFilterView'], 5]);

            $lastInsertId = dbLastInsertID('HS_Filters', 'xFilter');
            //If per user perm add them
            if ($fil['fType'] == 3 && isset($fil['fPermissionGroup']) && ! empty($fil['fPermissionGroup'])) {
                foreach ($fil['fPermissionGroup'] as $k=>$group) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Filter_Group(xFilter,xGroup) VALUES (?,?)', [$lastInsertId, $group]);
                }
            }
            if ($fil['fType'] == 4 && isset($fil['sPersonList']) && ! empty($fil['sPersonList'])) {
                foreach ($fil['sPersonList'] as $k=>$person) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Filter_People(xFilter,xPerson) VALUES (?,?)', [$lastInsertId, $person]);
                }
            }
        }
    } else {
        if ($fil['xFilter'] != 0) {
            $GLOBALS['DB']->Execute('UPDATE HS_Filters SET fShowCount=?,fCustomerFriendlyRSS=?,sFilterName=?,tFilterDef=?,sShortcut=?,fCacheNever=?,fDisplayTop=?,fType=?,sFilterView=? WHERE xFilter=?',
                                                    [$fil['fShowCount'], $fil['fCustomerFriendlyRSS'], $fil['sFilterName'], $fil['tFilterDef'], $fil['sShortcut'], $fil['fCacheNever'], $fil['fDisplayTop'], $fil['fType'], $fil['sFilterView'], $fil['xFilter']]);

            //If per user perm add them
            if ($fil['fType'] == 3 && isset($fil['fPermissionGroup']) && ! empty($fil['fPermissionGroup'])) {
                $GLOBALS['DB']->Execute('DELETE FROM HS_Filter_Group WHERE xFilter = ?', [$fil['xFilter']]);
                foreach ($fil['fPermissionGroup'] as $k=>$group) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Filter_Group(xFilter,xGroup) VALUES (?,?)', [$fil['xFilter'], $group]);
                }
            }
            if ($fil['fType'] == 4 && isset($fil['sPersonList']) && ! empty($fil['sPersonList'])) {
                $GLOBALS['DB']->Execute('DELETE FROM HS_Filter_People WHERE xFilter = ?', [$fil['xFilter']]);
                foreach ($fil['sPersonList'] as $k=>$person) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Filter_People(xFilter,xPerson) VALUES (?,?) ', [$fil['xFilter'], $person]);
                }
            }
        }
    }

    if ($fil['mode'] == 'add') {
        return $lastInsertId;
    } else {
        return true;
    }
}

/******************************************
GET FILTER
******************************************/
function apiGetFilter($filterid, $f, $l)
{
    $filterid = is_numeric($filterid) ? $filterid : 0;

    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Filters WHERE xFilter=?', [$filterid]);
}

/******************************************
REQUEST HISTORY STREAM
******************************************/
function apiGetFilterStream($reqids,$type,$last_history_item,$limit){
    $last_history_item = is_numeric($last_history_item) ? $last_history_item : 0;
    $limit = is_numeric($limit) ? $limit : 20;

    if (! $reqids) {
        return false;
    }

    //Set proper where
    if($type == 'stream'){
        $where = 'AND xPerson > 0 AND fPublic = 1';
    }elseif($type == 'stream-priv'){
        $where = 'AND xPerson > 0 AND '.dbStrLen('tNote').' > 0';
    }elseif($type == 'stream-cust'){
        $where = 'AND xPerson = 0 AND fPublic = 1';
    }elseif($type == 'stream-cust-staff'){
        $where = 'AND fPublic = 1';
    }

    //if no last history is sent then this is a first call so get the most current ones (and not getting old history)
    if($last_history_item == 0 && $limit >= 0){

        $history = $GLOBALS['DB']->SelectLimit('
        SELECT *
        FROM HS_Request_History
        WHERE xRequest IN ('.$reqids.') '.$where.'
        ORDER BY xRequestHistory DESC
        ',$limit,0,array());

    }elseif($limit < 0){ //load more button

        $history = $GLOBALS['DB']->SelectLimit('
        SELECT *
        FROM HS_Request_History
        WHERE xRequest IN ('.$reqids.') '.$where.' AND xRequestHistory < ?
        ORDER BY xRequestHistory DESC
        ',abs($limit),0,array($last_history_item));

    }else{ //live loading of newest

        $history = $GLOBALS['DB']->Execute('
        SELECT *
        FROM HS_Request_History
        WHERE xRequest IN ('.$reqids.') AND xRequestHistory > ? '.$where.'
        ORDER BY xRequestHistory DESC
        ', array($last_history_item));

    }

    return $history;
}

/******************************************
 * DELETE FILTER
 *****************************************
 * @param $filterid
 */
function apiDeleteFilter($filterid)
{
    $filterid = is_numeric($filterid) ? $filterid : 0;

    $GLOBALS['DB']->Execute('DELETE FROM HS_Filters WHERE xFilter=?', [$filterid]);
    return $GLOBALS['DB']->Execute('DELETE FROM HS_Filter_People WHERE xFilter=?', [$filterid]);
}

/******************************************
 * GET ALL FILTERS
 *****************************************
 * @param $userid
 * @param $zone
 * @return array
 */
function apiGetAllFilters($userid, $zone)
{
    global $user;

    //If limited to own requests return empty
    if (perm('fCanViewOwnReqsOnly')) {
        return [];
    }

    if ($zone == 'top') {
        $zonesql = ' AND fDisplayTop=1';
    } elseif ($zone == 'bottom') {
        $zonesql = ' AND fDisplayTop=0';
    } else {
        $zonesql = '';
    }

    $res = $GLOBALS['DB']->Execute('SELECT *
                                     FROM HS_Filters
                                     WHERE (xPerson = ? OR
                                           fType = 1 OR
                                           (fType = 2 AND xPerson = ?) OR
                                           (fType = 3 AND ? IN (SELECT xGroup FROM HS_Filter_Group WHERE xFilter = HS_Filters.xFilter)) OR
                                           (fType = 4 AND ? IN (SELECT xPerson FROM HS_Filter_People WHERE xFilter = HS_Filters.xFilter)))
                                           '.$zonesql.'
                                     ORDER BY sFilterName', [$userid, $userid, $user['fUserType'], $userid]);

    // return array with filterid as key
    $out = [];
    while ($v = $res->FetchRow()) {
        //FILTER-PERM
        //prev had limit here on global for l2 implement new check here
        // if($v['fGlobal'] == 1) continue; //skip this iteration of loop if it's a global filter and an L2 user

        //Instantiate auto_rule filter class
        $filter = hs_unserialize($v['tFilterDef']);

        $out[$v['xFilter']] = $filter->getFilterConditions();
        $out[$v['xFilter']]['xFilter'] = $v['xFilter'];
        $out[$v['xFilter']]['xPerson'] = $v['xPerson'];
        $out[$v['xFilter']]['fGlobal'] = $v['fGlobal'];
        $out[$v['xFilter']]['fShowCount'] = $v['fShowCount'];
        $out[$v['xFilter']]['fCustomerFriendlyRSS'] = $v['fCustomerFriendlyRSS'];
        $out[$v['xFilter']]['sShortcut'] = $v['sShortcut'];
        $out[$v['xFilter']]['sFilterName'] = $v['sFilterName'];
        $out[$v['xFilter']]['dtCachedCountAt'] = $v['dtCachedCountAt'];
        $out[$v['xFilter']]['iCachedCount'] = $v['iCachedCount'];
        $out[$v['xFilter']]['fCacheNever'] = $v['fCacheNever'];
        $out[$v['xFilter']]['fDisplayTop'] = $v['fDisplayTop'];
        $out[$v['xFilter']]['fType'] = $v['fType'];
        $out[$v['xFilter']]['fPermissionGroup'] = isset($v['fPermissionGroup']) ? $v['fPermissionGroup'] : 0;
        $out[$v['xFilter']]['sFilterView'] = $v['sFilterView'];
        $out[$v['xFilter']]['iCachedMinutes'] = $v['iCachedMinutes'];
    }

    return $out;
}

/******************************************
RETURN ARRAY OF FOLDERS IN ORDER
******************************************/
function apiCreateFolderList($filters)
{
    $out = [];
    foreach ($filters as $k=>$v) {
        $out[] = $v['sFilterFolder'];
    }
    $out = array_unique($out);
    asort($out);
    //handle global filter folder - bring to top

    //if a L2 user then don't show global folder
    /* FILTER-PERM
    if(isL2() || isGuest()){
        $i = array_search(lg_globalfilters,$out);
        if($i !== false){
            unset($out[$i]);
        }
    }
    */

    return $out;
}

/******************************************
RETURN A RANDOM WORD TO BE USED AS A PASS
******************************************/
function apiGetRequestPassword($reqid = '')
{
    if (empty($reqid)) {
        return randomPasswordString(20);
    } else {
        $r = apiGetRequest($reqid);

        return $r['sRequestPassword'];
    }
}

/******************************************
 * RETURN REPORTING TAGS FOR A REQUEST
 *****************************************
 * @param $reqid
 * @return array
 */
function apiGetRequestRepTags($reqid)
{
    if (is_numeric($reqid)) {
        $res = $GLOBALS['DB']->Execute('SELECT HS_Request_ReportingTags.xReportingTag, sReportingTag
                                         FROM HS_Request_ReportingTags,HS_Category_ReportingTags
                                     WHERE xRequest = ? AND HS_Request_ReportingTags.xReportingTag = HS_Category_ReportingTags.xReportingTag', [$reqid]);
        $out = [];
        while ($v = $res->FetchRow()) {
            $out[$v['xReportingTag']] = $v['sReportingTag'];
        }

        return $out;
    } else {
        return [];
    }
}

/******************************************
SET REPORTING TAGS FOR A REQUEST
******************************************/
function apiAddEditRequestRepTags($tags, $reqid, $catid, $f, $l)
{
    if (is_array($tags) && is_numeric($reqid) && is_numeric($catid)) {
        //Get rep tags for category
        $cattags = apiGetReportingTags($catid);
        $cattags = array_keys($cattags);

        $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

        //delete original tags that were set for this request
        $GLOBALS['DB']->Execute('DELETE FROM HS_Request_ReportingTags WHERE xRequest = ?', [$reqid]);

        foreach ($tags as $k=>$v) {
            //Make sure each tag is valid for the requests category
            if (in_array($v, $cattags)) {
                $GLOBALS['DB']->Execute('INSERT INTO HS_Request_ReportingTags(xRequest,xReportingTag) VALUES (?,?)', [$reqid, $v]);
            }
        }

        $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
        return true;
    }

    return false;
}

/******************************************
 * RETURN A REQUEST RESPONSE
 *****************************************
 * @param $reqresponseid
 * @return
 */
function apiGetRequestResponse($reqresponseid)
{
    $reqresponseid = is_numeric($reqresponseid) ? $reqresponseid : 0;

    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Responses WHERE xResponse = ?', [$reqresponseid]);
}

/**
 * Get a document by its id.
 *
 * @param $id
 * @return mixed
 */
function apiGetDocument($id)
{
    return $GLOBALS['DB']->GetRow('SELECT xDocumentId, sFilename, sFileMimeType, sFileLocation, sCID FROM HS_Documents WHERE xDocumentId = ?', [$id]);
}

/**
 * Get a document with all columns including the blob.
 * This can be huge amount of data returned.
 *
 * @param $id
 * @param $f
 * @param $l
 * @return mixed
 */
function apiGetDocumentWithBlob($id, $f, $l)
{
    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Documents WHERE xDocumentId = ?', [$id]);
}

/******************************************
 * RETURN ALL REQUEST RESPONSES
 *****************************************
 * @param $deleteflag
 * @param $xperson
 * @param $permgroup
 * @param $editing
 * @param $sortby
 */
function apiGetAllRequestResponses($deleteflag, $xperson, $permgroup, $editing, $sortby)
{
    $deleteflag = is_numeric($deleteflag) ? $deleteflag : 0;
    $xperson = is_numeric($xperson) ? $xperson : 0;

    $sortby = trim($sortby) != '' ? $sortby.',' : '';
    $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);

    if ($editing && isAdmin()) { //used on response creation page
        $personcheck = '1=1'; //Return everything for admins on response manage page
        $bindv = [$deleteflag];
    //array_push($bindv,0);
        //$personcheck = ' (HS_Responses.xPerson = ? OR HS_Responses.xPerson = ?) '; //return both users and global responses for admins
    } elseif ($editing) { //editing for regular users
        $bindv = [$deleteflag, $xperson];
        $personcheck = ' HS_Responses.xPerson = ? ';
    } else { //regular display in request.php
        $bindv = [$deleteflag, $xperson];
        array_push($bindv, $permgroup);
        array_push($bindv, $xperson);
        $personcheck = ' (HS_Responses.xPerson = ? OR
                            HS_Responses.fType = 1 OR
                            (fType = 3 AND ? IN (SELECT xGroup FROM HS_Response_Group WHERE xResponse = HS_Responses.xResponse)) OR
                            (fType = 4 AND ? IN (SELECT xPerson FROM HS_Response_People WHERE xResponse = HS_Responses.xResponse))) ';
    }

    return $GLOBALS['DB']->Execute('SELECT HS_Responses.*, '.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' AS fullname, '.dbConcat(' / ', 'HS_Responses.sFolder', 'HS_Responses.sResponseTitle').' AS pathname
                                     FROM HS_Responses, HS_Person
                                     WHERE HS_Responses.xPerson = HS_Person.xPerson AND HS_Responses.fDeleted = ? AND '.$personcheck.'
                                     ORDER BY '.$sortby.' sFolder ASC, sResponseTitle ASC', $bindv);
}

/**
 * RETURN RESPONSE FOLDERS.
 *
 * @param array $user
 *
 * @return array|bool
 */
function apiGetRequestResponseFolders(array $user)
{
    $xperson = is_numeric($user['xPerson']) ? $user['xPerson'] : 0;

    $res = $GLOBALS['DB']->Execute('SELECT DISTINCT sFolder FROM HS_Responses
		WHERE (xPerson = ? OR
				fType = 1 OR
				 (fType = 2 AND xPerson = ?) OR
				 (fType = 3 AND ? IN (SELECT xGroup FROM HS_Response_Group WHERE xGroup = HS_Response_Group.xGroup)) OR
				 (fType = 4 AND ? IN (SELECT xPerson FROM HS_Response_People WHERE xPerson = HS_Response_People.xPerson)))
				AND fDeleted=0
				ORDER BY sFolder ASC', [$xperson, $xperson, $user['xGroup'], $xperson]);

    return rsToArray($res, 'sFolder');
}

/******************************************
 * RETURN MOST USED RESPONSES FOR USER
 *****************************************
 * @param $xperson
 * @return
 */
function apiGetMostUsedResponses($xperson)
{
    $xperson = is_numeric($xperson) ? $xperson : 0;

    $cachekey = \HS\Cache\Manager::user_response_usage_key($xperson);

    return \Cache::remember($cachekey, \HS\Cache\Manager::CACHE_MOST_USED_RESPONSES_MINUTES, function () use ($xperson) {
        $threeMonthsAgo = \Carbon\Carbon::now()->subMonths(3)->timestamp;

        $responses = $GLOBALS['DB']->SelectLimit('SELECT HS_Responses.xResponse, HS_Responses.sResponseTitle, COUNT(HS_Stats_Responses.xEvent) AS stat_count
										 FROM HS_Responses,HS_Stats_Responses
										 WHERE HS_Responses.xResponse = HS_Stats_Responses.xResponse
										 	AND HS_Stats_Responses.xPerson = ?
										 	AND HS_Responses.fDeleted=0
										 	AND HS_Stats_Responses.dtGMTOccured > ?
										 GROUP BY HS_Responses.xResponse, HS_Responses.sResponseTitle
										 ORDER BY stat_count DESC', 10, 0, [$xperson, $threeMonthsAgo]);

        return $GLOBALS['DB']->_rs2rs($responses);
    });
}

function apiAddEditReport($res, $action = 'add') {
	global $user;

	$errors = [];
	$res['xReport'] = isset($res['xReport']) ? $res['xReport'] : '';
	$res['title'] = isset($res['title']) ? $res['title'] : '';
	$res['sPage'] = isset($res['sPage']) ? $res['sPage'] : 'reports';
	$res['sShow'] = isset($res['sShow']) ? $res['sShow'] : '';
	$res['mode'] = isset($res['mode']) ? $res['mode'] : 'add';
	$res['fType'] = isset($res['fType']) ? $res['fType'] : 2;
	$res['sFolder'] = isset($res['sFolder']) ? $res['sFolder'] : '';
	$res['fPermissionGroup'] = isset($res['fPermissionGroup']) ? $res['fPermissionGroup'] : 0;
	$res['sPersonList'] = isset($res['sPersonList']) ? $res['sPersonList'] : array();

	//Clean folders
	$folders = explode('/',$res['sFolder']);
	foreach($folders AS $k=>$folder){
		$folders[$k] = trim($folder);
	}
	$res['sFolder'] = implode(' / ',$folders);

	if(! empty($errors)){
		return $errors;
	}

	if ($action == 'add') {
		$rs = $GLOBALS['DB']->Execute('INSERT INTO HS_Saved_Reports (xPerson,sReport,sPage,sShow,sFolder,fType,tData)
										 VALUES (?,?,?,?,?,?,?)',array($user['xPerson'],$res['title'],$res['sPage'],$res['sShow'],$res['sFolder'],$res['fType'],hs_serialize($res)));
		//If per user perm add them
		$lastInsertId = dbLastInsertID('HS_Saved_Reports','xReport');
		$xreport = $lastInsertId;
		//If permission group, add them
		if($res['fType'] == 3 && isset($res['fPermissionGroup']) && !empty($res['fPermissionGroup'])){
			foreach($res['fPermissionGroup'] AS $k=>$group){
				$GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_Group(xReport,xGroup) VALUES (?,?)', array($lastInsertId,$group) );
			}
		}
		if($res['fType'] == 4 && isset($res['sPersonList']) && !empty($res['sPersonList'])){
			foreach($res['sPersonList'] AS $k=>$person){
				$GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_People(xReport,xPerson) VALUES (?,?)', array($lastInsertId,$person) );
			}
		}
	} else { // editing
		$xreport = $res['xReport'];
		$rs = $GLOBALS['DB']->Execute('UPDATE HS_Saved_Reports SET sReport = ?, sFolder = ?, fType = ?, tData = ? WHERE xReport = ?',
										array($res['title'], $res['sFolder'], $res['fType'], hs_serialize($res), $xreport));
		// If permission group, add them
		if($res['fType'] == 3 && isset($res['fPermissionGroup']) && !empty($res['fPermissionGroup'])){
			if (isAdmin()) {
				$GLOBALS['DB']->Execute( 'DELETE FROM HS_Report_Group WHERE xReport = ?', array($xreport) );
				foreach($res['fPermissionGroup'] AS $k=>$group){
					$GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_Group(xReport,xGroup) VALUES (?,?) ', array($xreport,$group) );
				}
			} else {
				// Since they aren't admins we only add their group to the existing. They can't take away groups.
				$existingGroups = $GLOBALS['DB']->GetCol( 'SELECT xGroup FROM HS_Report_Group WHERE xReport = ?', array($xreport) );
				foreach($res['fPermissionGroup'] AS $k=>$group){
					if ( ! in_array($group, $existingGroups)) {
						$GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_Group(xReport,xGroup) VALUES (?,?) ', array($xreport,$group) );
					}
				}
			}
		}
		if($res['fType'] == 4 && isset($res['sPersonList']) && !empty($res['sPersonList'])){
			$GLOBALS['DB']->Execute( 'DELETE FROM HS_Report_People WHERE xReport = ?', array($xreport) );
			foreach($res['sPersonList'] AS $k=>$person){
				$GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_People(xReport,xPerson) VALUES (?,?) ', array($xreport,$person) );
			}
		}
	}

	return $xreport;
}

/******************************************
ADD/EDIT RESPONSES
******************************************/
function apiAddEditResponse($res, &$files, $f, $l)
{
    global $user;
    // initialize
    $errors = [];
    $sql = '';
    $res['mode'] = isset($res['mode']) ? $res['mode'] : 'add';
    $res['resourceid'] = hs_numeric($res, 'resourceid') ? $res['resourceid'] : 0;

    $res['sResponseTitle'] = isset($res['sResponseTitle']) ? $res['sResponseTitle'] : '';
    $res['sFolder'] = isset($res['sFolder']) ? $res['sFolder'] : '';
    $res['tResponse'] = isset($res['tResponse']) ? $res['tResponse'] : '';
    $res['xPerson'] = hs_numeric($res, 'xPerson') ? $res['xPerson'] : $user['xPerson'];
    $res['fType'] = hs_numeric($res, 'fType') ? $res['fType'] : 2;
    $res['sTitle'] = isset($res['sTitle']) ? $res['sTitle'] : '';
    $res['xStatus'] = isset($res['xStatus']) ? $res['xStatus'] : '';
    $res['xCategory'] = isset($res['xCategory']) ? $res['xCategory'] : '';
    $res['xReportingTags'] = isset($res['xReportingTags']) ? $res['xReportingTags'] : '';
    $res['xPersonAssignedTo'] = isset($res['xPersonAssignedTo']) ? $res['xPersonAssignedTo'] : '';
    $res['fPublic'] = isset($res['fPublic']) ? $res['fPublic'] : '';
    $res['togroup'] = isset($res['togroup']) ? $res['togroup'] : '';
    $res['ccgroup'] = isset($res['ccgroup']) ? $res['ccgroup'] : '';
    $res['bccgroup'] = isset($res['bccgroup']) ? $res['bccgroup'] : '';
    $res['emailfrom'] = isset($res['emailfrom']) ? $res['emailfrom'] : '';
    $res['fRecurringRequest'] = isset($res['fRecurringRequest']) ? $res['fRecurringRequest'] : '';
    $res['dtSendsAt'] = calculateNextSend($res['fSendTime'], $res['fSendDay'], $res['fSendEvery']);

    //Create advanced array and encode to JSON
    $adv['sTitle'] = $res['sTitle'];
    $adv['togroup'] = $res['togroup'];
    $adv['ccgroup'] = $res['ccgroup'];
    $adv['bccgroup'] = $res['bccgroup'];
    $adv['emailfrom'] = $res['emailfrom'];
    $adv['xStatus'] = $res['xStatus'];
    $adv['xCategory'] = $res['xCategory'];
    $adv['xReportingTags'] = $res['xReportingTags'];
    $adv['xPersonAssignedTo'] = $res['xPersonAssignedTo'];
    $adv['fPublic'] = $res['fPublic'];
    $adv['sFirstName'] = $res['sFirstName'];
    $adv['sLastName'] = $res['sLastName'];
    $adv['sEmail'] = $res['sEmail'];
    $adv['sPhone'] = $res['sPhone'];
    $adv['sUserId'] = $res['sUserId'];

    if (! empty($GLOBALS['customFields'])) {
        foreach ($GLOBALS['customFields'] as $v) {
            $custid = 'Custom'.$v['fieldID'];
            $adv[$custid] = $res[$custid];

            //Handle drill downs, hold each drill element in it's own field for setting
            if ($v['fieldType'] == 'drilldown') {
                $ct = 1;
                $a = explode('#-#', $res[$custid]);
                foreach ($a as $k=>$v) {
                    $adv[$custid.'_'.$ct] = $v;
                    $ct++;
                }
            }
        }
    }

    //Make sure perm group is 0
    if ($res['fType'] != 3) {
        $res['fPermissionGroup'] = 0;
    }

    // Error checks
    if (hs_empty($res['sResponseTitle'])) {
        $errors['sResponseTitle'] = lg_admin_responses_er_title;
    }

    if ($res['fRecurringRequest']) {
        if (hs_empty($res['xCategory']) || $res['xCategory'] == 0) {
            $errors['xCategory'] = 'Category Required';
        }
    }

    //if(hs_empty($res['tResponse'])){
    //	$errors['tResponse'] = lg_admin_responses_er_response; }

    if (hs_empty($res['sFolder'])) {
        $errors['sFolder'] = lg_admin_responses_er_folder;
    }

    //Clean folders
    $folders = explode('/', $res['sFolder']);
    foreach ($folders as $k=>$folder) {
        $folders[$k] = trim($folder);
    }
    $res['sFolder'] = implode(' / ', $folders);

    // handle files
    foreach (Request::get('doc') as $doc) {
        $adv['attachment'][] = $doc;
    }
    if (Request::hasFile('doc')) {
        foreach (Request::file('doc') as $file) {
            if (! $file) {
                continue;
            }
            $adv['attachment'][] = apiAddAttachment($file);
        }
    }

    $res['tResponseOptions'] = json_encode($adv);

    if (empty($errors)) {
        if ($res['mode'] == 'add') {
            $GLOBALS['DB']->Execute('INSERT INTO HS_Responses(sResponseTitle,sFolder,tResponse,tResponseOptions,xPerson,fType, fSendEvery, fSendDay, fSendTime, dtSendsAt, fRecurringRequest) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                                        [
                                            $res['sResponseTitle'],
                                            $res['sFolder'],
                                            $res['tResponse'],
                                            $res['tResponseOptions'],
                                            $res['xPerson'],
                                            $res['fType'],
                                            $res['fSendEvery'],
                                            $res['fSendDay'],
                                            $res['fSendTime'],
                                            $res['dtSendsAt'],
                                            $res['fRecurringRequest']
                                        ]);

            $lastInsertId = dbLastInsertID('HS_Filters', 'xFilter');

            // If it has attachments add the relationship
            if ($adv['attachment']) {
                foreach ($adv['attachment'] as $id) {
                    $GLOBALS['DB']->Execute('UPDATE HS_Documents SET xResponse = ? WHERE xDocumentId = ?', [$lastInsertId, $id]);
                }
            }
            //If permission group, add them
            if ($res['fType'] == 3 && isset($res['fPermissionGroup']) && ! empty($res['fPermissionGroup'])) {
                foreach ($res['fPermissionGroup'] as $k=>$group) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Response_Group(xResponse,xGroup) VALUES (?,?)', [$lastInsertId, $group]);
                }
            }
            //If per user perm add them
            if ($res['fType'] == 4 && isset($res['sPersonList']) && ! empty($res['sPersonList'])) {
                foreach ($res['sPersonList'] as $k=>$person) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Response_People(xResponse,xPerson) VALUES (?,?)', [$lastInsertId, $person]);
                }
            }
        } elseif ($res['mode'] == 'edit') {
            $GLOBALS['DB']->Execute('UPDATE HS_Responses SET sResponseTitle=?, sFolder=?, tResponse=?, tResponseOptions=?, xPerson=?, fType=?, fSendEvery=?, fSendDay=?, fSendTime=?, dtSendsAt=?, fRecurringRequest=? WHERE xResponse = ?',
                                                                                        [$res['sResponseTitle'],
                                                                                        $res['sFolder'],
                                                                                        $res['tResponse'],
                                                                                        $res['tResponseOptions'],
                                                                                        $res['xPerson'],
                                                                                        $res['fType'],
                                                                                        $res['fSendEvery'],
                                                                                        $res['fSendDay'],
                                                                                        $res['fSendTime'],
                                                                                        $res['dtSendsAt'],
                                                                                        $res['fRecurringRequest'],
                                                                                        $res['resourceid'],
                                                                                        ]);

            // If it has attachments add the relationship
            if ($adv['attachment']) {
                foreach ($adv['attachment'] as $id) {
                    $GLOBALS['DB']->Execute('UPDATE HS_Documents SET xResponse = ? WHERE xDocumentId = ?', [$res['resourceid'], $id]);
                }
            }

            // If permission group, add them
            if ($res['fType'] == 3 && isset($res['fPermissionGroup']) && ! empty($res['fPermissionGroup'])) {
                if (isAdmin()) {
                    $GLOBALS['DB']->Execute('DELETE FROM HS_Response_Group WHERE xResponse = ?', [$res['resourceid']]);
                    foreach ($res['fPermissionGroup'] as $k=>$group) {
                        $GLOBALS['DB']->Execute('INSERT INTO HS_Response_Group(xResponse,xGroup) VALUES (?,?) ', [$res['resourceid'], $group]);
                    }
                } else {
                    // Since they aren't admins we only add their group to the existing. They can't take away groups.
                    $existingGroups = $GLOBALS['DB']->GetCol('SELECT xGroup FROM HS_Response_Group WHERE xResponse = ?', [$res['resourceid']]);
                    foreach ($res['fPermissionGroup'] as $k=>$group) {
                        if (! in_array($group, $existingGroups)) {
                            $GLOBALS['DB']->Execute('INSERT INTO HS_Response_Group(xResponse,xGroup) VALUES (?,?) ', [$res['resourceid'], $group]);
                        }
                    }
                }
            }
            //If per user perm add them
            elseif ($res['fType'] == 4 && isset($res['sPersonList']) && ! empty($res['sPersonList'])) {
                $GLOBALS['DB']->Execute('DELETE FROM HS_Response_People WHERE xResponse = ?', [$res['resourceid']]);
                foreach ($res['sPersonList'] as $k=>$person) {
                    $GLOBALS['DB']->Execute('INSERT INTO HS_Response_People(xResponse,xPerson) VALUES (?,?) ', [$res['resourceid'], $person]);
                }
            }
        }

        return true;
    } else {
        return $errors;
    }
}

/******************************************
SAVE REQUEST RESPONSE STAT EVENT
******************************************/
function apiResponseStatEvent($xresponse, $xrequest, $xperson)
{
    $xresponse = is_numeric($xresponse) ? $xresponse : 0;
    $xrequest = is_numeric($xrequest) ? $xrequest : 0;
    $xperson = is_numeric($xperson) ? $xperson : 0;

    return $GLOBALS['DB']->Execute('INSERT INTO HS_Stats_Responses(xResponse,xRequest,xPerson,dtGMTOccured)
                                    VALUES (?,?,?,?) ', [$xresponse, $xrequest, $xperson, time()]);
}

/******************************************
 * RETURN STAFF ALONG WITH NUMBER OF OPEN REQUESTS ASSIGNED TO THEM
 *****************************************
 * @return mixed
 */
function apiGetAssignStaff()
{
    return Cache::remember(\HS\Cache\Manager::CACHE_ASSIGNEDSTAFF_KEY, \HS\Cache\Manager::CACHE_ASSIGNEDSTAFF_MINUTES, function () {
        $out = [];
        $res = $GLOBALS['DB']->Execute('SELECT HS_Request.xPersonAssignedTo, count(HS_Request.xPersonAssignedTo) as openreqs
                                     FROM HS_Request
                                     WHERE HS_Request.fOpen = 1 AND
                                        HS_Request.fTrash = 0 AND
                                        HS_Request.xStatus != '.hs_setting('cHD_STATUS_SPAM').'
                                     GROUP BY HS_Request.xPersonAssignedTo');

        $assigncounts = rsToArray($res, 'xPersonAssignedTo', false);
        $users = apiGetAllUsersComplete();
        foreach ($users as $r) {
            $count = isset($assigncounts[$r['xPerson']]['openreqs']) ? $assigncounts[$r['xPerson']]['openreqs'] : 0;
            $out[$r['xPerson']] = $r;
            $out[$r['xPerson']]['namereq'] = $r['sFname'].' '.$r['sLname'].' ('.$count.')';
            $out[$r['xPerson']]['request_count'] = $count;
        }

        return $out;
    });
}

/******************************************
 * SUBSCRIBE TO A REQUEST
 *****************************************
 * @param $reqid
 * @param $person
 * @return bool
 */
function apiSubscribeToRequest($reqid, $person)
{
    if (is_numeric($reqid) && is_numeric($person)) {
        //Check that they're not already subscribed
        $chk = $GLOBALS['DB']->Execute('SELECT xPerson FROM HS_Subscriptions WHERE xRequest = ? AND xPerson = ?', [$reqid, $person]);

        if (! $chk->RecordCount()) {
            $GLOBALS['DB']->Execute('INSERT INTO HS_Subscriptions (xRequest,xPerson) VALUES (?,?)', [$reqid, $person]);
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/******************************************
 * UNSUBSCRIBE TO A REQUEST
 *****************************************
 * @param $reqid
 * @param $person
 */
function apiUnSubscribeToRequest($reqid, $person)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;
    $person = is_numeric($person) ? $person : 0;

    return $GLOBALS['DB']->Execute('DELETE FROM HS_Subscriptions WHERE xRequest = ? AND xPerson = ?', [$reqid, $person]);
}

/******************************************
 * RETURN ARRAY OF USERS SUBSCRIBED
 *****************************************
 * @param $reqid
 * @return array
 */
function apiGetRequestSubscribers($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    $out = [];
    $res = $GLOBALS['DB']->Execute('SELECT HS_Subscriptions.xPerson FROM HS_Subscriptions LEFT JOIN HS_Person ON HS_Subscriptions.xPerson = HS_Person.xPerson WHERE HS_Person.fDeleted = 0 AND HS_Subscriptions.xRequest = ?', [$reqid]);
    while ($r = $res->FetchRow()) {
        $out[] = $r['xPerson'];
    }

    return $out;
}

/******************************************
 * RETURN REQUESTS A USER IS SUBSCRIBED TO
 *****************************************
 * @param $xperson
 * @param $sortby
 */
function apiGetSubscribersByPerson($xperson, $sortby)
{
    $xperson = is_numeric($xperson) ? $xperson : 0;

    $sortby = trim($sortby) != '' ? $sortby.',' : '';
    $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);

    $res = $GLOBALS['DB']->Execute('SELECT HS_Request.*,HS_Subscriptions.xSubscriptions, '.dbConcat(' ', 'HS_Request.sFirstName', 'HS_Request.sLastName').' AS fullname
                                    FROM HS_Subscriptions,HS_Request
                                    WHERE HS_Request.xRequest = HS_Subscriptions.xRequest AND HS_Subscriptions.xPerson = ? AND HS_Request.fOpen = 1
                                    ORDER BY '.$sortby.' HS_Subscriptions.xRequest ASC', [$xperson]);
    $reqids = [];

    while ($row = $res->FetchRow()) {
        //build reqid list to get tNote's
        $reqids[] = $row['xRequest'];
    }
    //Reset dataset
    $res->Move(0);

    if (is_array($reqids) && ! empty($reqids)) {
        //Create subject lines with email title in front
        $rs = $GLOBALS['DB']->Execute('SELECT '.dbConcat(' #-#', 'HS_Request.sTitle', 'HS_Request_History.tNote').' AS tNote,tEmailHeaders,xRequestHistory,HS_Request_History.xRequest
                                            FROM HS_Request, HS_Request_History
                                            WHERE HS_Request.xRequest = HS_Request_History.xRequest AND HS_Request_History.fInitial = 1 AND
                                                  HS_Request_History.xRequest IN ('.implode(',', $reqids).')');

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                $GLOBALS['initReqArray'][$row['xRequest']] = $row['tNote'];
            }
        }

        //add in initial tNote which has be be grabbed separately from initial sql
        $res->Walk(function (&$item) {
            $item = (array) $item;
            $item['tNote'] = isset($GLOBALS['initReqArray'][$item['xRequest']]) ? $GLOBALS['initReqArray'][$item['xRequest']] : '';
        });

    }

    return $res;
}

/******************************************
 * MAKE A REQUEST HISTORY ITEM PRIVATE
 *****************************************
 * @param $reqhisid
 * @return bool
 */
function apiUnPublic($reqhisid)
{
    global $user;
    $reqhisid = is_numeric($reqhisid) ? $reqhisid : 0;

    $GLOBALS['DB']->Execute('UPDATE HS_Request_History SET fPublic = 0 WHERE xRequestHistory = ?', [$reqhisid]);
    $event = apiGetHistoryEvent($reqhisid);
    $description = lg_request_makelog.' "'.utf8_substr(str_replace(["\n", "\r"], ' ', hs_strip_tags($event['tNote'], true)), 0, 50).'" '.lg_request_makeprivlog;

    $newReqHisId = apiAddRequestHistory([
        'xRequest' => $event['xRequest'],
        'xPerson' => $user['xPerson'],
        'dtGMTChange' => time(),
        'fPublic' => 0,
        'fInitial' => 0,
        'iTimerSeconds' => '',
        'tLog' => 'Request Changed',
        'tNote' => '',
        'tEmailHeaders' => '',
    ]);

    $requestEvents = new \HS\Domain\Workspace\EventCollection();
    $requestEvents->add(new \HS\Domain\Workspace\Event([
        'xRequest' => $event['xRequest'],
        'xPerson' => $user['xPerson'],
        'sColumn' => 'fPublic',
        'dtLogged' => time(),
        'iValue' => 0,
        'sLabel' => lg_request_custupdatepriv,
        'sDescription' => $description,
    ]));

    return $requestEvents->flush($newReqHisId);
}

/******************************************
 * MAKE A REQUEST HISTORY ITEM PUBLIC
 *****************************************
 * @param $reqhisid
 * @return bool
 */
function apiMakePublic($reqhisid)
{
    global $user;
    $reqhisid = is_numeric($reqhisid) ? $reqhisid : 0;

    $GLOBALS['DB']->Execute('UPDATE HS_Request_History SET fPublic = 1 WHERE xRequestHistory = ?', [$reqhisid]);

    $event = apiGetHistoryEvent($reqhisid);
    $description = lg_request_makelog.' "'.utf8_substr(str_replace(["\n", "\r"], ' ', hs_strip_tags($event['tNote'], true)), 0, 50).'" '.lg_request_makepublog;

    $newReqHisId = apiAddRequestHistory([
        'xRequest' => $event['xRequest'],
        'xPerson' => $user['xPerson'],
        'dtGMTChange' => time(),
        'fPublic' => 0,
        'fInitial' => 0,
        'iTimerSeconds' => '',
        'tLog' => 'Request Changed',
        'tNote' => '',
        'tEmailHeaders' => '',
    ]);

    $requestEvents = new \HS\Domain\Workspace\EventCollection();
    $requestEvents->add(new \HS\Domain\Workspace\Event([
        'xRequest' => $event['xRequest'],
        'xPerson' => $user['xPerson'],
        'sColumn' => 'fPublic',
        'dtLogged' => time(),
        'iValue' => 1,
        'sLabel' => lg_request_custupdate,
        'sDescription' => $description,
    ]));

    return $requestEvents->flush($newReqHisId);
}

/******************************************
BREAK UP ACCESS KEY
******************************************/
function parseAccessKey($key)
{
    preg_match('/^([0-9]+)/', $key, $reqid);

    if (isset($reqid[1])) {
        $spass = str_replace($reqid[1], '', $key);

        return ['xRequest'=>$reqid[1], 'sRequestPassword'=>$spass];
    } else {
        return false;
    }
}

/******************************************
MAKE A CUSTOMER HISTORY SEARCH
******************************************/
function apiRequestCustHisSearch($args, $sortby = '')
{
    global $user;

    $sql = '';
    $values = [];
    $args['sUserId'] = isset($args['sUserId']) ? $args['sUserId'] : '';
    $args['sFirstName'] = isset($args['sFirstName']) ? $args['sFirstName'] : '';
    $args['sLastName'] = isset($args['sLastName']) ? $args['sLastName'] : '';
    $args['sEmail'] = isset($args['sEmail']) ? $args['sEmail'] : '';
    $args['sPhone'] = isset($args['sPhone']) ? $args['sPhone'] : '';
    $args['fOpenedVia'] = hs_numeric($args, 'fOpenedVia') ? $args['fOpenedVia'] : '';
    $args['all'] = isset($args['all']) ? $args['all'] : 1;

    if ($sortby != 'dtGMTOpened' && $sortby != 'xRequest') {
        $sortby = trim($sortby) != '' ? $sortby.',' : '';
        $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);
    } else {
        $sortby = '';
    }

    $join = $args['all'] == 1 ? ' AND ' : ' OR ';

    foreach ($args as $k=>$v) {
        //only do appropriate args
        if ($k == 'sUserId' || $k == 'sFirstName' || $k == 'sLastName' || $k == 'sEmail' || $k == 'sPhone' || $k == 'fOpenedVia') {
            if (! empty($v)) {
                if ($k == 'fOpenedVia') {
                    $t = $v;
                    $sql .= $k.' = ? '.$join;
                } else {
                    $t = wildCardLoc($v);

                    $sql .= $k.' '.dbLike().' ? '.$join;
                    /*
                    $sql .= ($t=wildCardLoc($v)) ? $k.' LIKE ? '.$join : $k.' =  ? '.$join;
                    */
                }

                $values[] = $t ? $t : $v;	//values for bind params
            }
        }
    }

    $sql = ! empty($sql) ? utf8_substr($sql, 0, strlen($sql) - 4) : ' 1=0';	//kill off last join

    //if can view own reqs only then limit
    if (perm('fCanViewOwnReqsOnly')) {
        $sql = $sql.' AND HS_Request.xPersonAssignedTo = '.$user['xPerson'];
    }

    // protect search results when in limited access mode
    if (perm('fLimitedToAssignedCats')) {
        $sql = $sql.' AND '. apiGetUserAssignedCatsWhere($user);
    }

    return $GLOBALS['DB']->SelectLimit('SELECT HS_Request.*, '.dbConcat(' #-#', 'HS_Request.sTitle', 'HS_Request_History.tNote').' AS tNote, '.dbConcat(' ', 'HS_Request.sFirstName', 'HS_Request.sLastName').' AS fullname
                                         FROM HS_Request
                                         LEFT OUTER JOIN HS_Request_History ON HS_Request.xRequest = HS_Request_History.xRequest
                                                AND HS_Request_History.fInitial = 1
                                         WHERE '.$sql.'
                                         ORDER BY '.$sortby.' dtGMTOpened DESC, HS_Request.xRequest DESC', hs_setting('cHD_MAXSEARCHRESULTS'), 0, $values);
}

/******************************************
SEARCH USED FOR THE HISTORY SEARCH TAB
******************************************/
//A little different from above, used in different areas. Should merge them at some point though.
function apiRequestHistorySearch($args, $cols, $f, $l)
{
    $sortby = isset($args['sortby']) ? $args['sortby'] : 'dtGMTOpened';
    $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);
    $sortord = isset($args['sortord']) ? $args['sortord'] : 'DESC';
    $sortord = (new HS\Http\Security)->cleanOrderDirection($sortord);

    $args['sUserId'] = isset($args['sUserId']) ? trim($args['sUserId']) : '';
    $args['sFirstName'] = isset($args['sFirstName']) ? trim($args['sFirstName']) : '';
    $args['sLastName'] = isset($args['sLastName']) ? trim($args['sLastName']) : '';
    $args['sEmail'] = isset($args['sEmail']) ? trim($args['sEmail']) : '';
    $args['sPhone'] = isset($args['sPhone']) ? trim($args['sPhone']) : '';
    $args['anyall'] = isset($args['anyall']) ? $args['anyall'] : 'all';
    $args['search_type'] = ! empty($args['search_type']) ? $args['search_type'] : 1;

    //Setup filter
    $ft = new hs_filter();
    $ft->filterDef['displayColumns'] = (! empty($cols) ? $cols : $GLOBALS['defaultWorkspaceCols']);
    $ft->filterDef['orderBy'] = $sortby;
    $ft->filterDef['orderByDir'] = $sortord;
    $ft->filterDef['urgentinline'] = true;
    $ft->filterDef['anyall'] = $args['anyall'];

    //General search
    if ($args['search_type'] == 1 && (! empty($args['sUserId']) || ! empty($args['sFirstName']) || ! empty($args['sLastName']) || ! empty($args['sEmail']))) {
        if (! empty($args['sFirstName'])) {
            $ft->filterDef['sFirstName'][0]['op'] = 'is';
            $ft->filterDef['sFirstName'][0]['value'] = $args['sFirstName'];
        }
        if (! empty($args['sLastName'])) {
            $ft->filterDef['sLastName'][0]['op'] = 'is';
            $ft->filterDef['sLastName'][0]['value'] = $args['sLastName'];
        }
        if (! empty($args['sEmail'])) {
            $ft->filterDef['sEmail'][0]['op'] = 'is';
            $ft->filterDef['sEmail'][0]['value'] = $args['sEmail'];
        }
        if (! empty($args['sUserId'])) {
            $ft->filterDef['sUserId'][0]['op'] = 'is';
            $ft->filterDef['sUserId'][0]['value'] = $args['sUserId'];
        }
        //Email address match
    } elseif ($args['search_type'] == 2 && ! empty($args['sEmail'])) {
        $ft->filterDef['sEmail'][0]['op'] = 'is';
        $ft->filterDef['sEmail'][0]['value'] = $args['sEmail'];
    //Name match
    } elseif ($args['search_type'] == 3 && ! empty($args['sFirstName']) && ! empty($args['sLastName'])) {
        $ft->filterDef['sFirstName'][0]['op'] = 'is';
        $ft->filterDef['sFirstName'][0]['value'] = $args['sFirstName'];

        $ft->filterDef['sLastName'][0]['op'] = 'is';
        $ft->filterDef['sLastName'][0]['value'] = $args['sLastName'];
    //Customer ID match
    } elseif ($args['search_type'] == 4 && ! empty($args['sUserId'])) {
        $ft->filterDef['sUserId'][0]['op'] = 'is';
        $ft->filterDef['sUserId'][0]['value'] = $args['sUserId'];
    //Email domain match
    } elseif ($args['search_type'] == 5 && ! empty($args['sEmail'])) {
        $email = explode('@', $args['sEmail']);
        $ft->filterDef['sEmail'][0]['op'] = 'ends_with';
        $ft->filterDef['sEmail'][0]['value'] = '@'.$email[1];
    //Last name match
    } elseif ($args['search_type'] == 6 && ! empty($args['sLastName'])) {
        $ft->filterDef['sLastName'][0]['op'] = 'is';
        $ft->filterDef['sLastName'][0]['value'] = $args['sLastName'];
    //First name search
    } elseif ($args['search_type'] == 7 && ! empty($args['sFirstName'])) {
        $ft->filterDef['sFirstName'][0]['op'] = 'is';
        $ft->filterDef['sFirstName'][0]['value'] = $args['sFirstName'];
    } elseif ($args['search_type'] == 8 && ! empty($args['sSearch'])) {
        $ft->filterDef['sSearch'] = $args['sSearch'];
    } elseif ($args['search_type'] == 9 && ! empty($args['sSearch'])) { // sidebar search
        $ft->filterDef['sSearch'] = $args['sSearch'];

        $ft->filterDef['sUserId'][0]['op'] = 'is';
        $ft->filterDef['sUserId'][0]['value'] = $args['sSearch'];

        $ft->filterDef['sFirstName'][0]['op'] = 'is';
        $ft->filterDef['sFirstName'][0]['value'] = $args['sSearch'];

        $ft->filterDef['sLastName'][0]['op'] = 'is';
        $ft->filterDef['sLastName'][0]['value'] = $args['sSearch'];


    //Send back empty set
    } else {
        $ft->filterDef['wheresql'] = '1=0';
    }

    //Find search results
    return $ft->outputResultSet();
}

/******************************************
 * ADD A REMINDER
 *****************************************
 * @param $args
 * @return bool
 */
function apiAddReminder($args)
{
    $args['xRequest'] = hs_numeric($args, 'xRequest') ? $args['xRequest'] : 0;
    $args['xPersonCreator'] = hs_numeric($args, 'xPersonCreator') ? $args['xPersonCreator'] : 0;
    $args['dtGMTReminder'] = hs_numeric($args, 'dtGMTReminder') ? $args['dtGMTReminder'] : 0;
    $args['tReminder'] = isset($args['tReminder']) ? $args['tReminder'] : '';
    $args['reminderto'] = isset($args['reminderto']) ? $args['reminderto'] : [];

    if ($args['xRequest'] != 0 && $args['xPersonCreator'] != 0 && $args['dtGMTReminder'] != 0 && ! hs_empty($args['tReminder'])) {
        $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

        $GLOBALS['DB']->Execute('INSERT INTO HS_Reminder(xRequest,xPersonCreator,dtGMTReminder,tReminder) VALUES (?,?,?,?)',
                                                        [$args['xRequest'], $args['xPersonCreator'], $args['dtGMTReminder'], $args['tReminder']]);
        $remid = dbLastInsertID('HS_Reminder', 'xReminder');

        //add reminders
        foreach ($args['reminderto'] as $v) {
            $GLOBALS['DB']->Execute('INSERT INTO HS_Reminder_Person(xReminder,xPerson) VALUES (?,?)', [$remid, $v]);
        }

        $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/

        return true;
    }

    return false;
}

/******************************************
GET REMINDERS FOR A REQ
******************************************/
function apiGetRemindersByReq($reqid, $xperson)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;
    $xperson = is_numeric($xperson) ? $xperson : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_Reminder.* FROM HS_Reminder,HS_Reminder_Person
                                     WHERE HS_Reminder.xRequest = ? AND HS_Reminder.xReminder = HS_Reminder_Person.xReminder AND HS_Reminder_Person.xPerson = ?
                                     ORDER BY dtGMTReminder ASC', [$reqid, $xperson]);
}

/******************************************
 * GET REMINDERS BY PERSON
 *****************************************
 * @param $xperson
 * @param $sortby
 */
function apiGetRemindersByPerson($xperson, $sortby)
{
    $xperson = is_numeric($xperson) ? $xperson : 0;

    $sortby = trim($sortby) != '' ? $sortby : 'dtGMTReminder ASC';
    $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);

    return $GLOBALS['DB']->Execute('SELECT HS_Reminder.* FROM HS_Reminder,HS_Reminder_Person
                                  WHERE HS_Reminder.xReminder = HS_Reminder_Person.xReminder AND HS_Reminder_Person.xPerson = ?
                                  ORDER BY '.$sortby, [$xperson]);
}

/******************************************
 * GET ALL REMINDERS WHICH NEED TO BE SENT
 *****************************************
 * @param $timestamp
 */
function apiGetAllCurrentReminders($timestamp)
{
    $timestamp = is_numeric($timestamp) ? $timestamp : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_Reminder.*,HS_Reminder_Person.xPerson, HS_Person.sEmail, HS_Person.sEmail2, HS_Person.fNotifyEmail, HS_Person.fNotifyEmail2
                                    FROM HS_Reminder,HS_Reminder_Person, HS_Person
                                    WHERE HS_Reminder.xReminder = HS_Reminder_Person.xReminder AND HS_Reminder_Person.xPerson = HS_Person.xPerson
                                        AND dtGMTReminder < ?', [$timestamp]);
}

/******************************************
 * DELETE A REMINDER
 *****************************************
 * @param $remid
 * @return mixed
 */
function apiDeleteReminder($remid)
{
    $remid = is_numeric($remid) ? $remid : 0;

    return \Illuminate\Support\Facades\DB::transaction(function() use($remid) {
        $res1 = $GLOBALS['DB']->Execute('DELETE FROM HS_Reminder_Person  WHERE xReminder = ?', [$remid]);
        $res2 = $GLOBALS['DB']->Execute('DELETE FROM HS_Reminder WHERE xReminder = ?', [$remid]);
        return ($res1 && $res2);
    });
}

/******************************************
GET LATEST REQUESTS
******************************************/
function apiGetLatestRequests($count, $f, $l)
{
    return $GLOBALS['DB']->SelectLimit('SELECT *,tNote FROM HS_Request,HS_Request_History
                                         WHERE HS_Request.xRequest = HS_Request_History.xRequest AND HS_Request_History.fInitial = 1
                                         ORDER BY dtGMTOpened DESC', $count, 0);
}

/******************************************
 * ADD A TIME LOG  FOR THE TIME TRACKER
 *****************************************
 * @param $args
 * @return false
 */
function apiAddTime($args)
{
    $args['xRequest'] = hs_numeric($args, 'xRequest') ? $args['xRequest'] : 0;
    $args['xPerson'] = hs_numeric($args, 'xPerson') ? $args['xPerson'] : 0;
    $args['dtGMTDate'] = isset($args['dtGMTDate']) ? jsDateToTime($args['dtGMTDate'], cHD_POPUPCALSHORTDATEFORMAT) : 0;
    $args['dtGMTDateAdded'] = hs_numeric($args, 'dtGMTDateAdded') ? $args['dtGMTDateAdded'] : time();
    $args['iMonth'] = hs_numeric($args, 'iMonth') ? $args['iMonth'] : 1;
    $args['iDay'] = hs_numeric($args, 'iDay') ? $args['iDay'] : 1;
    $args['iYear'] = hs_numeric($args, 'iYear') ? $args['iYear'] : date('Y');
    $args['tDescription'] = isset($args['tDescription']) ? $args['tDescription'] : '';
    $args['tTime'] = isset($args['tTime']) ? $args['tTime'] : '';
    $args['fBillable'] = hs_numeric($args, 'fBillable') ? $args['fBillable'] : 0;

    //Check that request exists
    if (apiGetRequest($args['xRequest']) === false) {
        return false;
    }

    if ($args['dtGMTDate']) {
        $date = $args['dtGMTDate'];
    } else {
        $date = mktime(0, 0, 0, $args['iMonth'], $args['iDay'], $args['iYear']);
    }
    $time = parseTimeToSeconds($args['tTime']);

    //If the description just holds the default let's strip it
    if ($args['tDescription'] == lg_request_timedesc) {
        $args['tDescription'] = '';
    }

    $GLOBALS['DB']->Execute('INSERT INTO HS_Time_Tracker(xRequest,xPerson,iSeconds,dtGMTDate,dtGMTDateAdded,tDescription,fBillable)
                                    VALUES (?,?,?,?,?,?,?)',
                                        [$args['xRequest'],
                                              $args['xPerson'],
                                              $time,
                                              $date,
                                              $args['dtGMTDateAdded'],
                                              $args['tDescription'],
                                              $args['fBillable'], ]);

    return dbLastInsertID('HS_Time_Tracker', 'xTimeId');
}

/******************************************
 * DELETE TIME LOG FOR THE TIME TRACKER
 *****************************************
 * @param $args
 */
function apiDeleteTime($args)
{
    $args['xTimeId'] = hs_numeric($args, 'xTimeId') ? $args['xTimeId'] : 0;

    return $GLOBALS['DB']->Execute('DELETE FROM HS_Time_Tracker WHERE xTimeId = ?', [$args['xTimeId']]);
}

/******************************************
 * GET ALL THE TIME FOR A REQUEST
 *****************************************
 * @param $reqid
 * @return
 */
function apiGetTimeForRequest($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Time_Tracker WHERE xRequest=? ORDER BY dtGMTDate DESC,dtGMTDateAdded ASC', [$reqid]);
}

/******************************************
 * GET SECONDS FOR A REQUEST
 *****************************************
 * @param $reqid
 * @return
 */
function apiGetTimeTotal($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->GetOne('SELECT SUM(iSeconds) FROM HS_Time_Tracker WHERE HS_Time_Tracker.xRequest = ?', [$reqid]);
}

/******************************************
 * SEARCH TIME EVENTS
 *****************************************
 * @param $data
 * @return
 */
function apiTimeTrackerSearch($data)
{
    $where = '';
    $bind = [];

    if (isset($data['start_time'])) {
        $where .= ' AND dtGMTDate >= ? ';
        array_push($bind, $data['start_time']);
    }
    if (isset($data['end_time'])) {
        $where .= ' AND dtGMTDate <  ? ';
        array_push($bind, $data['end_time']);
    }
    if (isset($data['sUserId'])) {
        $where .= ' AND sUserId = ? ';
        array_push($bind, $data['sUserId']);
    }
    if (isset($data['sEmail'])) {
        $where .= ' AND sEmail = ? ';
        array_push($bind, $data['sEmail']);
    }
    if (isset($data['sFirstName'])) {
        $where .= ' AND sFirstName = ? ';
        array_push($bind, $data['sFirstName']);
    }
    if (isset($data['sLastName'])) {
        $where .= ' AND sLastName = ? ';
        array_push($bind, $data['sLastName']);
    }
    if (isset($data['fOpen'])) {
        $where .= ' AND fOpen = ? ';
        array_push($bind, $data['fOpen']);
    }
    if (isset($data['xStatus'])) {
        $where .= ' AND xStatus = ? ';
        array_push($bind, $data['xStatus']);
    }
    if (isset($data['xMailbox'])) {
        $where .= ' AND xMailbox = ? ';
        array_push($bind, $data['xMailbox']);
    }
    if (isset($data['fOpenedVia'])) {
        $where .= ' AND fOpenedVia = ? ';
        array_push($bind, $data['fOpenedVia']);
    }
    if (isset($data['xCategory'])) {
        $where .= ' AND xCategory = ? ';
        array_push($bind, $data['xCategory']);
    }
    if (isset($data['fUrgent'])) {
        $where .= ' AND fUrgent = ? ';
        array_push($bind, $data['fUrgent']);
    }
    if (isset($data['xPersonAssignedTo'])) {
        $where .= ' AND xPersonAssignedTo = ? ';
        array_push($bind, $data['xPersonAssignedTo']);
    }
    if (isset($data['xPersonOpenedBy'])) {
        $where .= ' AND xPersonOpenedBy = ? ';
        array_push($bind, $data['xPersonOpenedBy']);
    }

    if (is_array($GLOBALS['customFields'])) {
        foreach ($GLOBALS['customFields'] as $fvalue) {
            if (isset($data['Custom'.$fvalue['fieldID']])) {
                $where .= ' AND Custom'.$fvalue['fieldID'].' = ? ';
                array_push($bind, $data['Custom'.$fvalue['fieldID']]);
            }
        }
    }

    return $GLOBALS['DB']->Execute('SELECT HS_Time_Tracker.*, HS_Request.sUserId, HS_Request.sFirstName, HS_Request.sLastName, HS_Request.sEmail
                                    FROM HS_Time_Tracker,HS_Request
                                    WHERE HS_Time_Tracker.xRequest = HS_Request.xRequest '.$where.'
                                    ORDER BY '.$data['orderBy'].' '.$data['orderByDir'], $bind);
}

/******************************************
RETURN ALL THE CC/BCC FOR A REQUEST
******************************************/
function getEmailGroups($req)
{
    $out = [];
    $breaks = ["\r\n", "\n", "\r"];
    $out['last_cc'] = [];
    $out['last_bcc'] = [];
    $out['inactive_cc'] = [];
    $out['inactive_bcc'] = [];
    $all_ccs = [];
    $in_first_histitem = true;
    //Get all req history items
    $history = apiGetRequestHistory($req['xRequest']);

    // Track emails sent in External notes
    $externalEmailPool = [];

    //Get mailboxes, don't include HS mailboxes in CC/BCC list
    $mboxes = [utf8_strtolower(hs_setting('cHD_NOTIFICATIONEMAILACCT'))];
    $mailboxesres = apiGetAllMailboxes(0, '');
    if (is_object($mailboxesres) && $mailboxesres->RecordCount() > 0) {
        while ($box = $mailboxesres->FetchRow()) {
            if (! hs_empty($box['sReplyEmail'])) {
                $mboxes[] = utf8_strtolower($box['sReplyEmail']);
            }
        }
    }

    /*
        Handling External Notes:
        If an outgoing note is EXTERNAL, the tLog field will have an 'emailtogroup' with a list of 1+ emails.
        External notes may also have email addresses CCed or BCCed.

        Any emails used in External note's CC or BCC fields should be eliminated from the public CC/BCC list
        on both the incoming (tEmailHeaders) and outgoing (tLog) side.

        Edge cases:
        1. Same person CCed/BCCed on external is also CCed/BCCed on a public note
           In this case, that person should still remain CCed on Public note

        Steps:
        1. Make a pool of email addresses from all External notes.
        2. Make a pool of email addresses from all Public notes ($out['last_cc'] + $out['last_bcc'])
        3. Remove email addresses from the External pool which are in the Public pool (for edge case #1)
        4. Remove any email addresses from the final CC/BCC list that remain in the External pool
    */

    //Loop over and get last email and all others
    if (hs_rscheck($history)) {
        while ($row = $history->FetchRow()) {

            //Check staff updates for cc/bcc info
            if (! hs_empty($row['tLog']) || ($row['fPublic'] == 1 && $row['xPerson'] > 0)) { //If there's a log or if it's a public note made by a staff member then proceed
                $log = hs_unserialize($row['tLog']);

                if (hs_empty($log['emailtogroup'])) { //To group is only used in external notes, this line keeps external notes from populating public note cc/bcc
                    if (! hs_empty($log['emailccgroup']) || ! hs_empty($log['emailbccgroup'])) {
                        if (! hs_empty($log['emailccgroup'])) {
                            $emails = explode(',', $log['emailccgroup']);
                            foreach ($emails as $email) {
                                if (empty($email) || in_array(strtolower($email), $mboxes) || trim($email) == trim($req['sEmail'])) {
                                    continue;
                                }

                                $email = trim(str_replace($breaks, '', $email));
                                $all_ccs[] = $email;

                                if ($in_first_histitem) {
                                    $out['last_cc'][] = $email;
                                } else {
                                    $out['inactive_cc'][] = $email;
                                }
                            }
                        }

                        if (! hs_empty($log['emailbccgroup'])) {
                            $emails = explode(',', $log['emailbccgroup']);
                            foreach ($emails as $email) {
                                if (empty($email) || in_array(strtolower($email), $mboxes) || trim($email) == trim($req['sEmail'])) {
                                    continue;
                                }

                                $email = trim(str_replace($breaks, '', $email));
                                $all_ccs[] = $email;

                                if ($in_first_histitem) {
                                    $out['last_bcc'][] = $email;
                                } else {
                                    $out['inactive_bcc'][] = $email;
                                }
                            }
                        }
                    } elseif ($row['fPublic'] == 1 && $row['xPerson'] > 0) {
                        //If there was no log, but the note was public and by a staff member then we don't want anything in the last cc/bcc
                        $in_first_histitem = false;
                    }
                } else {
                    /**
                     * 1. Make a pool of email addresses from all External notes.
                     */

                    // Add "to", "cc" and "bcc" addresses from external notes
                    $poolEmails = [];
                    $poolEmails['to'] = explode(',', $log['emailtogroup']); // Will "always" have 1+ "to" addresses
                    $poolEmails['cc'] = (hs_empty($log['emailccgroup'])) ? [] : explode(',', $log['emailccgroup']);
                    $poolEmails['bcc'] = (hs_empty($log['emailbccgroup'])) ? [] : explode(',', $log['emailbccgroup']);

                    foreach ($poolEmails as $emailGroup) {
                        foreach ($emailGroup as $email) {
                            if (empty($email) || in_array(strtolower($email), $mboxes) || trim($email) == trim($req['sEmail'])) {
                                continue;
                            }
                            $externalEmailPool[] = $email;
                        }
                    }
                }
            }

            //Check for emails in, take an cc/bcc info out of there if an email is the last correspondance
            if (! hs_empty($row['tEmailHeaders'])) {
                $headers = hs_unserialize($row['tEmailHeaders']);
                if (isset($headers['cc'])) {
                    $cc = hs_charset_emailheader(hs_parse_email_list($headers['cc']));
                    $cc = explode(',', $cc);

                    foreach ($cc as $email) {
                        if (trim($email) == trim($req['sEmail'])) {
                            continue;
                        } //Skip customers email account

                        if (! in_array(utf8_strtolower($email), $mboxes)) {
                            $email = trim(str_replace($breaks, '', $email));
                            $all_ccs[] = $email;

                            if ($in_first_histitem) {
                                $out['last_cc'][] = $email;
                            } else {
                                $out['inactive_cc'][] = $email;
                            }
                        }
                    }
                }

                //Take people in TO other than HS mailbox and make them CC's
                /* Problem here is people forward to one mailbox and this then adds that forwarded address as a CC
                if(isset($headers['to'])){
                    $to = hs_charset_emailheader(hs_parse_email_list($headers['to']));
                    $to = explode(',', $to);

                    foreach($to AS $email){
                        if(trim($email) == trim($req['sEmail'])) continue; //Skip customers email account

                        if(!in_array(strtolower($email),$mboxes)){
                            $email = trim(str_replace($breaks,'',$email));
                            $all_ccs[] = $email;

                            if($in_first_histitem){
                                $out['last_cc'][] = $email;
                            }else{
                                $out['inactive_cc'][] = $email;
                            }
                        }
                    }
                }
                */
            }

            //Check staff updates for cc/bcc info
            if (! hs_empty($row['tLog']) || ($row['fPublic'] == 1 && $row['xPerson'] > 0)) { //If there's a log or if it's a public note made by a staff member then proceed
                $log = hs_unserialize($row['tLog']);

                if (hs_empty($log['emailtogroup'])) { //To group is only used in external notes, this line keeps external notes from populating public note cc/bcc
                    if (! hs_empty($log['emailccgroup']) || ! hs_empty($log['emailbccgroup'])) {
                        if (! hs_empty($log['emailccgroup'])) {
                            $emails = explode(',', $log['emailccgroup']);
                            foreach ($emails as $email) {
                                if (empty($email) || in_array(utf8_strtolower($email), $mboxes) || trim($email) == trim($req['sEmail'])) {
                                    continue;
                                }

                                $email = trim(str_replace($breaks, '', $email));
                                $all_ccs[] = $email;

                                if ($in_first_histitem) {
                                    $out['last_cc'][] = $email;
                                } else {
                                    $out['inactive_cc'][] = $email;
                                }
                            }
                        }

                        if (! hs_empty($log['emailbccgroup'])) {
                            $emails = explode(',', $log['emailbccgroup']);
                            foreach ($emails as $email) {
                                if (empty($email) || in_array(utf8_strtolower($email), $mboxes) || trim($email) == trim($req['sEmail'])) {
                                    continue;
                                }

                                $email = trim(str_replace($breaks, '', $email));
                                $all_ccs[] = $email;

                                if ($in_first_histitem) {
                                    $out['last_bcc'][] = $email;
                                } else {
                                    $out['inactive_bcc'][] = $email;
                                }
                            }
                        }
                    } elseif ($row['fPublic'] == 1 && $row['xPerson'] > 0) {
                        //If there was no log, but the note was public and by a staff member then we don't want anything in the last cc/bcc
                        $in_first_histitem = false;
                    }
                }
            }

            //Prevent a cc'd user who emails in from being removed from the cc list. We get the
            //latest from here and if it's someone who's ever been cc'd we make sure they're back on the cc list
            if ($in_first_histitem) {
                $latest_from = hs_charset_emailheader(hs_parse_email_list($headers['from']));
            }

            //Var used to set last CC/BCC group apart from all others
            if (! empty($out['last_cc']) || ! empty($out['last_bcc'])) {
                $in_first_histitem = false;
            }
        }
    }

    //If the from has ever been a CC add them in so we don't drop cc people who reply (because they're not part of the last cc group since they are the from)
    if (in_array($latest_from, $all_ccs)) {
        $out['last_cc'][] = $latest_from;
    }

    //find difference between last group and inactive group. Remove all active from inactive list.
    $out['inactive_cc'] = array_diff($out['inactive_cc'], $out['last_cc']);
    $out['inactive_bcc'] = array_diff($out['inactive_bcc'], $out['last_bcc']);

    // 2. Make a pool of email addresses from all Public notes ($out['last_cc'] + $out['last_bcc'])
    $publicEmailPool = array_merge($out['last_cc'], $out['last_bcc']);

    // 3. Remove email addresses from pool found in public notes (for edge case #1)
    $emailsToRemove = array_diff($externalEmailPool, $publicEmailPool);

    // 4. Remove any email addresses in CC/BCC that are also in the pool
    $out['last_cc'] = array_diff($out['last_cc'], $emailsToRemove);
    $out['last_bcc'] = array_diff($out['last_bcc'], $emailsToRemove);

    // These are converted back to strings
    $out['last_cc'] = implode(',', array_unique($out['last_cc']));
    $out['last_bcc'] = implode(',', array_unique($out['last_bcc']));
    $out['inactive_cc'] = implode(',', array_unique($out['inactive_cc']));
    $out['inactive_bcc'] = implode(',', array_unique($out['inactive_bcc']));

    return $out;
}

/******************************************
FIND IF A REQUEST ID HAS BEEN MERGED
******************************************/
function apiCheckIfMerged($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->GetOne(' SELECT xRequest FROM HS_Request_Merged WHERE xMergedRequest = ? ', [$reqid]);
}

/******************************************
FIND IF A REQUEST ID HAS BEEN MERGED and the access key is valid
******************************************/
function apiCheckIfMergedValid($reqid, $accesskey)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;
    return $GLOBALS['DB']->GetOne('SELECT xRequest FROM HS_Request_Merged WHERE xMergedRequest = ? and sRequestPassword = ?', [$reqid, $accesskey]);
}

/******************************************
MERGE A REQUEST
******************************************/
function apiMergeRequests($req_from, $req_into)
{
    global $user;

    $doLocking = hs_setting('cFORCE_MERGE_LOCKING', false);

    $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/
    //On MySQL we need to manually lock this operation because it takes a long time (relatively) and if accessing concurrently
    // you could end up with both requests deleted (if trying to merge into each other at the same time)
    //Note this must be updated if any tables are added which are affected here or in apiDeleteRequest.
    if ($doLocking && (config('database.default') == 'mysql')) {
        $GLOBALS['DB']->Execute('LOCK TABLES HS_Request_Merged WRITE,
									  HS_Assignment_Chain WRITE,
									  HS_Subscriptions WRITE,
									  HS_Reminder WRITE,
									  HS_Request_History WRITE,
									  HS_Time_Tracker WRITE,
									  HS_Request_Pushed WRITE,
									  HS_Request_Note_Drafts WRITE,
									  HS_Request_ReportingTags WRITE,
									  HS_Documents WRITE,
									  HS_Request WRITE
									  HS_Request_Events WRITE;');
    }

    //Confirm that both to and from exist
    $check_to = apiGetRequest($req_into);
    $check_from = apiGetRequest($req_from);

    if ($check_to != false && $check_from != false && is_numeric($req_from) && is_numeric($req_into) && ($req_from != $req_into)) {

                //Move all request items to the new request
        // notes: reporting tags not updated to avoid conflicts
        $GLOBALS['DB']->Execute('UPDATE HS_Request_Merged SET xRequest = ? WHERE xRequest = ?', [$req_into, $req_from]);
        $GLOBALS['DB']->Execute('UPDATE HS_Subscriptions SET xRequest = ? WHERE xRequest = ?', [$req_into, $req_from]);
        $GLOBALS['DB']->Execute('UPDATE HS_Reminder SET xRequest = ? WHERE xRequest = ?', [$req_into, $req_from]);
        $GLOBALS['DB']->Execute('UPDATE HS_Request_History SET xRequest = ?, fMergedFromRequest = ?, fInitial = ? WHERE xRequest = ?', [$req_into, $req_from, 0, $req_from]);
        $GLOBALS['DB']->Execute('UPDATE HS_Time_Tracker SET xRequest = ? WHERE xRequest = ?', [$req_into, $req_from]);
        $GLOBALS['DB']->Execute('UPDATE HS_Request_Events SET xRequest = ? WHERE xRequest = ?', [$req_into, $req_from]);

        // Clean up subscriptions so someone is on the merge twice. See #491
        $subscriptions = $GLOBALS['DB']->Execute('SELECT xSubscriptions, xPerson FROM HS_Subscriptions WHERE xRequest = ?', [$req_into]);
        $userIds = [];
        while ($row = $subscriptions->FetchRow()) {
            if (in_array($row['xPerson'], $userIds)) {
                $GLOBALS['DB']->Execute('DELETE FROM HS_Subscriptions WHERE xSubscriptions = ?', [$row['xSubscriptions']]);

                break;
            }
            $userIds[] = $row['xPerson'];
        }

        //Insert ID's into merge table
        $GLOBALS['DB']->Execute('INSERT INTO HS_Request_Merged(xMergedRequest, xRequest, sRequestPassword) VALUES (?,?,?) ', [$req_from, $req_into, $check_from['sRequestPassword']]);

        $ret = true;
    } else {
        $ret = false;
    }

    //Remove special MySQL lock
    if ($doLocking && (config('database.default') == 'mysql')) {
        $GLOBALS['DB']->Execute('UNLOCK TABLES');
    }

    $transBad = $GLOBALS['DB']->HasFailedTrans();

    $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/

    if (! $transBad) {
        // Fire if the transaction is good. No need to queue events here
        event('request.merge', [$req_from, $req_into]);

        //Delete from request, under it's own transaction
        // Which will trigger an "request.delete" event, which needs to happen after the merge event
        // Check to ensure we aren't merging into ourselves, so as not to delete an incorrect request
        if ($req_from != $req_into) {
            apiDeleteRequest($req_from);
            logMsg('DELETED from merge: '.$req_from.' into '.$req_into);
        }
    }

    //Insert a note about the merge if we had positive result. We need to do this outside the Lock because notifications fail due to a query in notification class which is not in locks listed above.
    if ($ret) {
        //update the iLastReplyBy column to match the newly merged request history.
        $iLastReplyBy = $GLOBALS['DB']->GetOne('select xPerson from HS_Request_History where xRequest = ? and fPublic = 1 order by dtGMTChange desc',[$req_into]);
        if ($iLastReplyBy) {
            $GLOBALS['DB']->Execute('update HS_Request set iLastReplyBy = ? where xRequest = ?',[$iLastReplyBy, $req_into]);
        }

        //Insert note and event
        $newReqHistoryId = apiAddRequestHistory([
            'xRequest' => $req_into,
            'xPerson' => $user['xPerson'],
            'dtGMTChange' => time(),
            'tLog' => 'Request Changed:',
            'tNote' => '',
        ]);

        $requestEvents = new \HS\Domain\Workspace\EventCollection();
        $requestEvents->add(new \HS\Domain\Workspace\Event([
            'xRequest' => $req_into,
            'xPerson' => $user['xPerson'],
            'sColumn' => 'xRequest',
            'dtLogged' => time(),
            'sValue' => json_encode(['to' => $req_into, 'from' => $req_from]),
            'sLabel' => $req_into,
            'sDescription' => sprintf(lg_lookup_21, $req_from),
        ]));

        $notifier = new hs_notify($req_into, $newReqHistoryId, $user['xPerson'], __FILE__, __LINE__);
        $notifier->SetRequestType('existing');
        $notifier->Notify();

        return $requestEvents->flush($newReqHistoryId);
    }

    return $ret;
}

/********************************************
Do the request push
*********************************************/
function doRequestPush($reqid, $push_option, $comment)
{
    $isobject = true;
    $id = '';
    $user = apiGetLoggedInUser();
    $user['xPerson'] = ($user['xPerson'] > 0 ? $user['xPerson'] : -1); //adjustment to show the creating user as the system

    // Check and ensure the file exists
    $fileName = customCodePath('RequestPush-'.clean_filename($push_option).'.php');
    if (! file_exists($fileName)) {
        return false;
    }

    //Include push file
    require_once $fileName;

    //Init class
    $name = 'RequestPush_'.$push_option;
    if (class_exists($name)) {
        $rp = new $name;
    }

    if (is_object($rp)) {
        //Get the request details
        $req = apiGetRequest($reqid);
        $req_events = apiGetRequestHistory($reqid);

        //Adjust comment to replace placeholders
        \HS\View\Mail\TemplateTemporaryFile::create('request_push_comment', $comment);
        $comment = restrictedView('request_push_comment', getPlaceholders([], $req));

        //Convert to format expected by push class
        $req_array = $req;

        //Add in assigned users information as well as person doing push
        $assigned_user = apiGetUserPlaceholders($req_array['xPersonAssignedTo']);
        $req_array['assigned_person_fname'] = $assigned_user['sFname'];
        $req_array['assigned_person_lname'] = $assigned_user['sLname'];
        $req_array['assigned_person_email'] = $assigned_user['sEmail'];

        $req_array['acting_person_xperson'] = $user['xPerson'];
        $req_array['acting_person_fusertype'] = $user['fUserType'];
        $req_array['acting_person_fname'] = $user['sFname'];
        $req_array['acting_person_lname'] = $user['sLname'];
        $req_array['acting_person_email'] = $user['sEmail'];

        //Comment and history
        $req_array['staff_comment'] = $comment;
        $req_array['request_history'] = hs_clean_req_history_for_API($reqid, $req_array);

        //Do push
        $id = $rp->push($req_array);
        $id = $id ? $id : ''; //if push doesn't return anything make sure we make this an empty string or else the insert fails in sql server

        if (empty($rp->errorMsg) && $req) {
            //Save push details
            $GLOBALS['DB']->Execute('INSERT INTO HS_Request_Pushed (xRequest,xPerson,dtGMTPushed,sPushedTo,sReturnedID,tComment) VALUES (?,?,?,?,?,?)', [$reqid, $user['xPerson'], time(), $push_option, $id, $comment]);
        }
    } else {
        $isobject = false;
    }

    return ['id'=>$id, 'isobject'=>$isobject, 'errors'=>($rp->errorMsg ? $rp->errorMsg : '')];
}

/********************************************
Determine if any request push classes built
*********************************************/
function listRequestPushClasses()
{
    $files = listFilesInDir(customCodePath('/'));

    $i = 0;
    $rpclasses = [];
    foreach ($files as $k=>$v) {
        if (substr($v, 0, 11) == 'RequestPush' && ! utf8_strpos($v, '-base')) {
            $rpclasses[$i]['file'] = $v;
            preg_match("/RequestPush-([a-zA-Z0-9_\-\.]*)\.php/", $v, $match);
            $rpclasses[$i]['name'] = $match[1];
        }
        $i++;
    }

    return $rpclasses;
}

/******************************************
GET PUSHES FOR A REQ
******************************************/
function apiGetPushesByReq($reqid, $f, $l)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_Request_Pushed.* FROM HS_Request_Pushed
                                     WHERE HS_Request_Pushed.xRequest = ?
                                     ORDER BY dtGMTPushed DESC', [$reqid]);
}

/*****************************************
CREATE TABLE FOR HISTORY OF PUSHES
*****************************************/
function showPushesByReq($reqid)
{
    $pushedlist = '';
    $pushedrs = apiGetPushesByReq($reqid, __FILE__, __LINE__);
    if (hs_rscheck($pushedrs)) {
        $pushedlist .= '<table width="100%" style="margin-bottom:0px;">';
        while ($r = $pushedrs->FetchRow()) {
            $pushedlist .= '<tr>';
            $pushedlist .= '<td width="90"><b>'.$r['sPushedTo'].'</b></td>';
            if (! hs_empty($r['sReturnedID'])) {
                $adminPushDetailsUrl = action('Admin\AdminBaseController@adminFileCalled', [
                    'pg' => 'ajax_gateway',
                    'action' => 'push_details',
                    'xPushed' => $r['xPushed'],
                ]);
                $pushedlist .= '<td width="40" align="right"><a href="#" class="btn inline-action" onclick="hs_overlay({href:\''.$adminPushDetailsUrl.'\', width:700,title:\''.lg_request_pushdetailsbox.'\'});return false;">'.lg_request_pushdetails.'</a></td>';
            } else {
                $pushedlist .= '<td width="40" align="right"></td>';
            }
            $pushedlist .= '</tr>';
            $pushedlist .= '<tr><td colspan="2" style="font-size:85%;">'.hs_showShortDate($r['dtGMTPushed']).'<br /><br /></td></tr>';
        }
        $pushedlist .= '</table>';
    }

    return $pushedlist;
}

/*****************************************
CREATE A DRAFT
*****************************************/
function apiCreateDraft($reqid, $xperson, $note)
{
    $GLOBALS['DB']->Execute('INSERT INTO HS_Request_Note_Drafts(xRequest,xPerson,dtGMTSaved,tNote) VALUES (?,?,?,?)', [$reqid, $xperson, time(), $note]);
    $ct = apiGetDrafts($reqid, $xperson);

    return $ct->RecordCount();
}

/*****************************************
FIND A STAFFERS DRAFTS FOR A REQUEST
*****************************************/
function apiGetDrafts($reqid, $xperson)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;
    $xperson = is_numeric($xperson) ? $xperson : 0;

    return $GLOBALS['DB']->Execute('SELECT *
                                     FROM HS_Request_Note_Drafts
                                     WHERE xRequest = ? AND xPerson = ?
                                     ORDER BY dtGMTSaved DESC', [$reqid, $xperson]);
}

/*****************************************
RETURN A SPECIFIC DRAFT
*****************************************/
function apiGetDraft($xdraft)
{
    $xdraft = is_numeric($xdraft) ? $xdraft : 0;

    return $GLOBALS['DB']->GetRow('SELECT *
                                    FROM HS_Request_Note_Drafts
                                    WHERE xDraft = ?', [$xdraft]);
}

/*****************************************
DELETE A STAFFERS REQUEST DRAFTS
*****************************************/
function apiDeleteRequestPersonDrafts($reqid, $xperson)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;
    $xperson = is_numeric($xperson) ? $xperson : 0;

    return $GLOBALS['DB']->Execute('DELETE FROM HS_Request_Note_Drafts WHERE xRequest = ? AND xPerson = ?', [$reqid, $xperson]);
}

/*****************************************
DELETE A REQUESTS DRAFTS
*****************************************/
function apiDeleteRequestDrafts($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->Execute('DELETE FROM HS_Request_Note_Drafts WHERE xRequest = ?', [$reqid]);
}

/*****************************************
RETURN ALL REQUESTS FOR AN PORTAL EMAIL
*****************************************/
function apiPortalRequestHistoryForEmail($email, $type)
{
    $where = '';
    $bv = [];

    if ($type == 1) { //return by email
        $where = 'sEmail = ?';
        $bv = [$email];
    } elseif ($type == 2) { //return by customer ID's associated with that email
        $sql = [' sEmail = ? '];
        $bv = [$email];

        $ids = $GLOBALS['DB']->Execute('SELECT DISTINCT sUserId FROM HS_Request WHERE sEmail = ? AND sUserId <> ?', [$email, '']);

        while ($r = $ids->FetchRow()) {
            $sql[] = 'sUserId = ?';
            $bv[] = $r['sUserId'];
        }

        $where = implode(' OR ', $sql);
    }

    $res = $GLOBALS['DB']->Execute('SELECT HS_Request.*, HS_Category.sCategory,HS_luStatus.sStatus,'.dbConcat(' #-#', 'HS_Request.sTitle', 'HS_Request_History.tNote').' AS tNote, '.dbConcat(' ', 'HS_Request.sFirstName', 'HS_Request.sLastName').' AS fullname
                                         FROM HS_Request
                                         LEFT OUTER JOIN HS_Request_History ON HS_Request.xRequest = HS_Request_History.xRequest AND HS_Request_History.fInitial = 1 AND HS_Request_History.fPublic = 1
                                         LEFT OUTER JOIN HS_Category ON HS_Request.xCategory = HS_Category.xCategory
                                         LEFT OUTER JOIN HS_luStatus ON HS_Request.xStatus = HS_luStatus.xStatus
                                         WHERE '.$where.'
                                         ORDER BY dtGMTOpened DESC', $bv);

    //Clean request history note
    $data = rsToArray($res, 'xRequest', false);
    foreach ($data as $k=>$v) {
        $data[$k]['accesskey'] = $data[$k]['xRequest'].$data[$k]['sRequestPassword']; //add accesskey var

        $data[$k]['tNote'] = utf8_wordwrap(strip_tags(initRequestClean($data[$k]['tNote'], ($data[$k]['fOpenedVia'] != 1 ? true : false))), 60, ' ', true);
    }

    //Restrict to just this portal and selected mailboxes if we're in a multiportal
    if (isset($GLOBALS['hs_multiportal'])) {
        foreach ($data as $id=>$req) {
            if (! $GLOBALS['hs_multiportal']->requestValidForPortal($req)) {
                unset($data[$id]);
            }
        }
    }

    return $data;
}

/*****************************************
CREATE ADDRESS BOOK CONTACT
*****************************************/
function apiCreateABContact($contact)
{
    $GLOBALS['DB']->Execute('INSERT INTO HS_Address_Book(sFirstName,sLastName,sEmail,sTitle,sDescription,fHighlight) VALUES (?,?,?,?,?,?)',
                                    [trim($contact['sFirstName']), trim($contact['sLastName']), trim($contact['sEmail']), trim($contact['sTitle']), trim($contact['sDescription']), $contact['fHighlight']]);

    return true;
}

/*****************************************
DELETE ADDRESS BOOK CONTACT
*****************************************/
function apiDeleteABContact($contactid)
{
    $contactid = is_numeric($contactid) ? $contactid : 0;

    return $GLOBALS['DB']->Execute('DELETE FROM HS_Address_Book WHERE xContact = ?', [$contactid]);
}

/*****************************************
RETURN LIST OF INTERNAL ADDRESS BOOK CONTACTS
*****************************************/
function apiGetABContacts()
{
    return $GLOBALS['DB']->Execute('SELECT *
                                    FROM HS_Address_Book
                                    ORDER BY fHighlight DESC, sLastName ASC, sFirstName ASC');
}
