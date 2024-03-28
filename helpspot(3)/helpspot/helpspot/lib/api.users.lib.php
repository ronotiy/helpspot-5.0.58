<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/******************************************
UPLOAD USER IMAGE
******************************************/
function apiUserImageUpload($image)
{

    // initialize
    $image['xPerson'] = hs_numeric($image, 'xPerson') ? $image['xPerson'] : 0;
    $image['sDescription'] = isset($image['sDescription']) ? $image['sDescription'] : '';
    $image['sFilename'] = isset($image['sFilename']) ? $image['sFilename'] : '';
    $image['sFileMimeType'] = isset($image['sFileMimeType']) ? $image['sFileMimeType'] : '';
    $image['blobPhoto'] = isset($image['blobPhoto']) ? $image['blobPhoto'] : '';

    $GLOBALS['DB']->StartTrans(); /******* START TRANSACTION ******/

    $GLOBALS['DB']->Execute('INSERT INTO HS_Person_Photos(xPerson,sDescription,sFilename,sFileMimeType,sSeries) VALUES (?,?,?,?,?)',
                                                [$image['xPerson'], $image['sDescription'], $image['sFilename'],
                                                      $image['sFileMimeType'], 'upload', ]);

    $id = dbLastInsertID('HS_Person_Photos', 'xPersonPhotoId');
    $GLOBALS['DB']->UpdateBlob('HS_Person_Photos', 'blobPhoto', $image['blobPhoto'], ' xPersonPhotoId = '.$id);

    $GLOBALS['DB']->CompleteTrans(); /******* END TRANSACTION ******/


    return $id;
}

/******************************************
RETURN USERS SPECIFIC LIST OF IMAGES
******************************************/
function apiGetUserImages($user, $f, $l)
{

    // initialize
    $user['xPerson'] = hs_numeric($user, 'xPerson') ? $user['xPerson'] : '';
    $user['xPersonPhotoId'] = hs_numeric($user, 'xPersonPhotoId') ? $user['xPersonPhotoId'] : 0;

    return $GLOBALS['DB']->Execute('SELECT xPersonPhotoId FROM HS_Person_Photos
                            WHERE xPerson = 0 OR xPerson = ? OR xPersonPhotoId = ?
                            ORDER BY xPerson DESC, sFilename ASC', [$user['xPerson'], $user['xPersonPhotoId']]);
}

/******************************************
ADD/EDIT USER
******************************************/
function apiAddEditUser($user, $f, $l)
{
    // initialize
    $errors = [];
    $user['mode'] = isset($user['mode']) ? $user['mode'] : 'add';
    $user['resourceid'] = hs_numeric($user, 'resourceid') ? $user['resourceid'] : 0;
    $user['updatecats'] = isset($user['updatecats']) ? $user['updatecats'] : true;

    $user['sFname'] = isset($user['sFname']) ? hs_truncate($user['sFname'], 50) : '';
    $user['sLname'] = isset($user['sLname']) ? hs_truncate($user['sLname'], 100) : '';
    $user['sUsername'] = isset($user['sUsername']) ? hs_truncate($user['sUsername'], 100) : '';
    $user['sEmail'] = isset($user['sEmail']) ? hs_truncate($user['sEmail'], 255) : '';
    $user['sSMS'] = isset($user['sSMS']) ? hs_truncate($user['sSMS'], 50) : '';
    $user['sEmail2'] = isset($user['sEmail2']) ? hs_truncate($user['sEmail2'], 255) : '';
    $user['sPhone'] = isset($user['sPhone']) ? hs_truncate($user['sPhone'], 32) : '';
    $user['tSignature'] = isset($user['tSignature']) ? $user['tSignature'] : '';
    $user['tSignature_HTML'] = isset($user['tSignature_HTML']) ? $user['tSignature_HTML'] : '';
    $user['xSMSService'] = hs_numeric($user, 'xSMSService') ? $user['xSMSService'] : 0;
    $user['sPassword'] = ! hs_empty($user['sPassword']) ? trim($user['sPassword']) : '';
    $user['xPersonPhotoId'] = hs_numeric($user, 'xPersonPhotoId') ? $user['xPersonPhotoId'] : 0;
    $user['xPersonPhotoId_reset'] = isset($user['xPersonPhotoId_reset']) ? $user['xPersonPhotoId_reset'] : false;
    $user['sEmoji'] = isset($user['sEmoji']) ? $user['sEmoji'] : '';
    $user['fUserType'] = hs_numeric($user, 'fUserType') ? $user['fUserType'] : 2;
    $user['fDarkMode'] = hs_numeric($user, 'fDarkMode') ? $user['fDarkMode'] : 0;
    $user['fNotifyEmail'] = hs_numeric($user, 'fNotifyEmail') ? $user['fNotifyEmail'] : 0;
    $user['fNotifyEmail2'] = hs_numeric($user, 'fNotifyEmail2') ? $user['fNotifyEmail2'] : 0;
    $user['fNotifySMS'] = hs_numeric($user, 'fNotifySMS') ? $user['fNotifySMS'] : 0;

    $user['fDefaultToPublic'] = hs_numeric($user, 'fDefaultToPublic') ? $user['fDefaultToPublic'] : 0;
    $user['fHideWysiwyg'] = hs_numeric($user, 'fHideWysiwyg') ? $user['fHideWysiwyg'] : 0;
    $user['fHideImages'] = hs_numeric($user, 'fHideImages') ? $user['fHideImages'] : 0;
    $user['fReturnToReq'] = hs_numeric($user, 'fReturnToReq') ? $user['fReturnToReq'] : 0;
    $user['fSidebarSearchFullText'] = hs_numeric($user, 'fSidebarSearchFullText') ? $user['fSidebarSearchFullText'] : 0;
    $user['iRequestHistoryLimit'] = hs_numeric($user, 'iRequestHistoryLimit') ? $user['iRequestHistoryLimit'] : 10;
    $user['fRequestHistoryView'] = hs_numeric($user, 'fRequestHistoryView') ? $user['fRequestHistoryView'] : 1;
    $user['fKeyboardShortcuts'] = hs_numeric($user, 'fKeyboardShortcuts') ? $user['fKeyboardShortcuts'] : 0;
    $user['sHTMLEditor'] = isset($user['sHTMLEditor']) ? $user['sHTMLEditor'] : '';

    $user['tWorkspace'] = isset($user['tWorkspace']) ? $user['tWorkspace'] : '';
    $user['sWorkspaceDefault'] = isset($user['sWorkspaceDefault']) ? $user['sWorkspaceDefault'] : 'myq';

    $user['xCatList'] = isset($user['xCatList']) ? $user['xCatList'] : [];
    $user['fNotifySMSUrgent'] = hs_numeric($user, 'fNotifySMSUrgent') ? $user['fNotifySMSUrgent'] : 0;
    $user['xPersonOutOfOffice'] = hs_numeric($user, 'xPersonOutOfOffice') ? $user['xPersonOutOfOffice'] : 0;
    $user['fNotifyNewRequest'] = hs_numeric($user, 'fNotifyNewRequest') ? $user['fNotifyNewRequest'] : 0;
    $user['emailnewuser'] = isset($user['emailnewuser']) ? $user['emailnewuser'] : false;

    // Error checks
    if (hs_empty($user['sFname'])) {
        $errors['sFname'] = lg_admin_users_er_fname;
    }
    if (hs_empty($user['sLname'])) {
        $errors['sLname'] = lg_admin_users_er_lname;
    }
    if (hs_empty($user['sEmail']) || ! validateEmail($user['sEmail'])) {
        $errors['sEmail'] = lg_admin_users_er_email;
    }
    if (! hs_empty($user['sEmail2'])) {
        if (! validateEmail($user['sEmail2'])) {
            $errors['sEmail2'] = lg_admin_users_er_email2;
        }
    }
    if (! hs_empty($user['sPassword']) && strlen($user['sPassword']) < 8) {
        $errors['sPassword'] = lg_admin_users_er_passlen;
    }

    // if using ldap don't check username if not check for a password
    if (hs_setting('cAUTHTYPE') == 'blackbox' || hs_setting('cAUTHTYPE') == 'ldap_ad') {
        if (hs_empty($user['sUsername'])) {
            $errors['sUsername'] = lg_admin_users_er_user;
        }
    }

    //check for duplicate emails
    $checkemail = $GLOBALS['DB']->GetRow('SELECT COUNT(*) as etotal FROM HS_Person WHERE sEmail = ? AND xPerson <> ?', [$user['sEmail'], $user['resourceid']]);

    if (is_array($checkemail) && $checkemail['etotal'] != 0) {
        $errors['errorBoxText'] = lg_admin_users_er_emaildup;
        $errors['sEmail'] = '';
    }

    //check for duplicate usernames, ignore empty since it's empty when using internal
    if (! hs_empty($user['sUsername'])) {
        $checkusername = $GLOBALS['DB']->GetRow('SELECT COUNT(*) as etotal FROM HS_Person WHERE sUsername = ? AND xPerson <> ?', [$user['sUsername'], $user['resourceid']]);

        if (is_array($checkusername) && $checkusername['etotal'] != 0) {
            $errors['errorBoxText'] = lg_admin_users_er_usernamedup;
            $errors['sUsername'] = '';
        }
    }

    //Make sure if the SMS notifications are checked that there's an SMS phone number
    if (($user['fNotifySMS'] || $user['fNotifySMSUrgent']) && hs_empty($user['sSMS'])) {
        $errors['sSMS'] = lg_admin_users_er_sms;
    }

    //check that email isn't an email which HS checks as a mailbox. Only do this check for new users.
    require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
    $mailboxes = apiGetAllMailboxes(0, '');

    while ($box = $mailboxes->FetchRow()) {
        if ($box['sReplyEmail'] == $user['sEmail']) {
            $errors['errorBoxText'] = lg_admin_users_er_mailboxcheck;
            $errors['sEmail'] = '';
        }
    }

    if ($user['mode'] == 'add') {
        if (hs_empty($user['sPassword'])) {
            $errors['sPassword'] = lg_admin_users_er_pass;
        }
    }

    // If uploading a new photo reset the emoji if there is one
    if ($user['xPersonPhotoId_reset'] == true) {
        $user['sEmoji'] = null;
    }

    // Convert emoji avatars to shortnames
    if ($user['sEmoji']){
        $client = new \JoyPixels\Client(new \JoyPixels\Ruleset());
        $user['sEmoji'] = $client->toShort($user['sEmoji']);
    }

    if (empty($errors)) {
        //Setup password hasher
        $hasher = new PasswordHash(4, false);

        if ($user['mode'] == 'add') {

            $GLOBALS['DB']->Execute('INSERT INTO HS_Person(sFname,sLname,sUsername,sEmail,sEmail2,sSMS,xSMSService,sPasswordHash,sPhone,tSignature,tSignature_HTML,fNotifyEmail,
															fNotifyEmail2,fNotifySMS,fNotifySMSUrgent,xPersonPhotoId,fUserType,xPersonOutOfOffice,
															fDefaultToPublic,fHideWysiwyg,fHideImages,fReturnToReq,iRequestHistoryLimit,fRequestHistoryView,sHTMLEditor,fNotifyNewRequest,tWorkspace,sWorkspaceDefault,fSidebarSearchFullText,fKeyboardShortcuts, sEmoji, fDarkMode)
					 							  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                                                            [$user['sFname'],
                                                            $user['sLname'],
                                                            $user['sUsername'],
                                                            $user['sEmail'],
                                                            $user['sEmail2'],
                                                            $user['sSMS'],
                                                            $user['xSMSService'],
                                                            $hasher->HashPassword($user['sPassword']),
                                                            $user['sPhone'],
                                                            $user['tSignature'],
                                                            $user['tSignature_HTML'],
                                                            $user['fNotifyEmail'],
                                                            $user['fNotifyEmail2'],
                                                            $user['fNotifySMS'],
                                                            $user['fNotifySMSUrgent'],
                                                            $user['xPersonPhotoId'],
                                                            $user['fUserType'],
                                                            $user['xPersonOutOfOffice'],
                                                            $user['fDefaultToPublic'],
                                                            $user['fHideWysiwyg'],
                                                            $user['fHideImages'],
                                                            $user['fReturnToReq'],
                                                            $user['iRequestHistoryLimit'],
                                                            $user['fRequestHistoryView'],
                                                            $user['sHTMLEditor'],
                                                            $user['fNotifyNewRequest'],
                                                            $user['tWorkspace'],
                                                            $user['sWorkspaceDefault'],
                                                            $user['fSidebarSearchFullText'],
                                                            $user['fKeyboardShortcuts'],
                                                            $user['sEmoji'],
                                                            $user['fDarkMode'], ]);

            $userID = dbLastInsertID('HS_Person', 'xPerson');
        } elseif ($user['mode'] == 'edit') {
            $GLOBALS['DB']->Execute('UPDATE HS_Person SET sFname=?,sLname=?,sUsername=?,sEmail=?,sEmail2=?,sSMS=?,xSMSService=?,sPasswordHash=?,
															sPhone=?,tSignature=?,tSignature_HTML=?,fNotifyEmail=?,fNotifyEmail2=?,fNotifySMS=?,fNotifySMSUrgent=?,xPersonPhotoId=?,fUserType=?,
															xPersonOutOfOffice=?,fDefaultToPublic=?,fHideWysiwyg=?,fHideImages=?,fReturnToReq=?,iRequestHistoryLimit=?,fRequestHistoryView=?,sHTMLEditor=?,fNotifyNewRequest=?,tWorkspace=?,sWorkspaceDefault=?,fSidebarSearchFullText=?,fKeyboardShortcuts=?,sEmoji=?,fDarkMode=?
												 WHERE xPerson = ?',
                                                        [$user['sFname'],
                                                        $user['sLname'],
                                                        $user['sUsername'],
                                                        $user['sEmail'],
                                                        $user['sEmail2'],
                                                        $user['sSMS'],
                                                        $user['xSMSService'],
                                                        (! hs_empty($user['sPassword']) ? $hasher->HashPassword($user['sPassword']) : $GLOBALS['DB']->GetOne('SELECT sPasswordHash FROM HS_Person WHERE xPerson = ?', [$user['resourceid']])),
                                                        $user['sPhone'],
                                                        $user['tSignature'],
                                                        $user['tSignature_HTML'],
                                                        $user['fNotifyEmail'],
                                                        $user['fNotifyEmail2'],
                                                        $user['fNotifySMS'],
                                                        $user['fNotifySMSUrgent'],
                                                        $user['xPersonPhotoId'],
                                                        $user['fUserType'],
                                                        $user['xPersonOutOfOffice'],
                                                        $user['fDefaultToPublic'],
                                                        $user['fHideWysiwyg'],
                                                        $user['fHideImages'],
                                                        $user['fReturnToReq'],
                                                        $user['iRequestHistoryLimit'],
                                                        $user['fRequestHistoryView'],
                                                        $user['sHTMLEditor'],
                                                        $user['fNotifyNewRequest'],
                                                        $user['tWorkspace'],
                                                        $user['sWorkspaceDefault'],
                                                        $user['fSidebarSearchFullText'],
                                                        $user['fKeyboardShortcuts'],
                                                        $user['sEmoji'],
                                                        $user['fDarkMode'],
                                                        $user['resourceid'], ]);
            $userID = true;
        }

        //Change cat assignments
        if ($user['updatecats'] == true) {
            $catuserid = ! empty($user['resourceid']) ? $user['resourceid'] : $userID;
            addRemovePersonFromCat($catuserid, $user['xCatList'], $f, $l);
        }

        if ($user['emailnewuser']) {
            $vars = [
                'email_subject' => sprintf(lg_mail_newstaffsub, hs_setting('cHD_ORGNAME')),
                'email' => $user['sEmail'],
                'password' => $user['sPassword'],
                'helpspoturl' => action('Admin\AdminBaseController@adminFileCalled'),
            ];

            $message = (new \HS\Mail\Mailer\MessageBuilder(\HS\Mail\SendFrom::default()))
                ->to($user['sEmail'])
                ->subject('newstaff', $vars)
                ->body('newstaff', '', $vars);

            \HS\Jobs\SendMessage::dispatch($message)
                ->onQueue(config('queue.high_priority_queue')); // mail.private
        }

        return $userID;
    } else {
        return $errors;
    }
}

/**
 * Remove a person from all categories.
 *
 * @param $xPerson
 * @return bool|string
 */
function removePersonFromAllCats($xPerson)
{
    $allCats = apiGetAllCategoriesComplete();
    $allStaff = apiGetAllUsers();

    if (! is_object($allCats) or empty($xPerson) or $xPerson == 0) {
        return false;
    }

    while ($cat = $allCats->FetchRow()) {
        $assigned = hs_unserialize($cat['sPersonList']);

        $newList = \Arr::where($assigned, function ($key, $user) use ($xPerson) {
            return $user != $xPerson;
        });

        //set new default category person if this person was previously the default
        if ($cat['xPersonDefault'] != $xPerson) {
            $newDefault = $cat['xPersonDefault'];
        } else {
            if (count($newList) > 0) {
                $newDefault = $newList[0];
            } else {
                //If this was also the last person assigned to the cat then just grab someone!
                reset($allStaff);
                $key = key($allStaff);
                $newList[] = intval($allStaff[$key]['xPerson']);
                $newDefault = intval($allStaff[$key]['xPerson']);
            }
        }

        apiUpdateCategoryPersonList($cat['xCategory'], $newDefault, $newList);
    }

    \Facades\HS\Cache\Manager::forgetGroup('categories');

    return ''; // Previously returned error messages
}

/******************************************
ADD/REMOVE USER FROM ASSIGNED CATEGORIES
******************************************/
function addRemovePersonFromCat($xperson, $inTheseCats, $f, $l)
{
    $allcats = apiGetAllCategoriesComplete();
    $allstaff = apiGetAllUsersComplete();

    if (is_object($allcats) && ! empty($xperson) && $xperson > 0) {
        while ($cat = $allcats->FetchRow()) {
            $newlist = [];

            //Cats the user should be in
            if (in_array($cat['xCategory'], $inTheseCats)) {
                $oldlist = hs_unserialize($cat['sPersonList']);

                //if in old list then do nothing else update
                if (! in_array($xperson, $oldlist)) {
                    $oldlist[] = $xperson; //add to oldlist

                    //loop over all users to build new list. Doing this will put users in correct order because allstaff is in correct order
                    foreach ($allstaff as $k=>$v) {
                        if (in_array($k, $oldlist)) {
                            $newlist[] = $k;
                        }
                    }

                    apiUpdateCategoryPersonList($cat['xCategory'], $cat['xPersonDefault'], $newlist);
                }

                //Cats the user shouldn't be in
            } else {
                $oldlist = hs_unserialize($cat['sPersonList']);
                //Check that user isn't in here, if he is then rebuild list and update
                if (in_array($xperson, $oldlist)) {
                    foreach ($oldlist as $v) {
                        if ($v != $xperson) {
                            $newlist[] = $v;
                        }	//add to new list as long as it's not the person we're trying to remove
                    }

                    //set new default category person if this person was previously the default
                    if ($cat['xPersonDefault'] == $xperson) {
                        if (count($newlist) > 0) {
                            $newdefault = $newlist[0];
                        } else {
                            //If this was also the last person assigned to the cat then just grab someone!
                            reset($allstaff);
                            $key = key($allstaff);
                            $newlist[] = intval($allstaff[$key]['xPerson']);
                            $newdefault = intval($allstaff[$key]['xPerson']);
                        }
                    } else {
                        $newdefault = $cat['xPersonDefault'];
                    }

                    apiUpdateCategoryPersonList($cat['xCategory'], $newdefault, $newlist);
                }
            }
        }
    }

    \Facades\HS\Cache\Manager::forgetGroup('categories');

    return ''; // Previously returned error messages
}

/******************************************
 * UPDATE LIST OF CATEGORY ASSIGNED PEOPLE
 *****************************************
 * @param $catid
 * @param $defaultPerson
 * @param $newlist
 */
function apiUpdateCategoryPersonList($catid, $defaultPerson, $newlist)
{
    $list = hs_serialize($newlist);

    return $GLOBALS['DB']->Execute('UPDATE HS_Category SET sPersonList = ?, xPersonDefault = ? WHERE xCategory = ?', [$list, $defaultPerson, $catid]);
}

/******************************************
RETURN IF THE USER IS A DEFAULT IN ANY CATEGORY
******************************************/
function apiIsADefaultContact($xperson)
{
    return $GLOBALS['DB']->GetOne('SELECT COUNT(*) AS thecount FROM HS_Category WHERE xPersonDefault = ?', [$xperson]);
}
