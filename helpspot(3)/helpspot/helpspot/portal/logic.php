<?php
/*****************************************
PHP GLOBAL SETTINGS
*****************************************/
//We are in the portal
define('IN_PORTAL', true);

/*****************************************
INCLUDE PATH
*****************************************/
set_include_path(cBASEPATH.'/helpspot/pear'.PATH_SEPARATOR.cBASEPATH.'/helpspot/lib');

/*****************************************
INCLUDES
*****************************************/
require_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
require_once cBASEPATH.'/helpspot/lib/error.lib.php';
require_once cBASEPATH.'/helpspot/lib/display.lib.php';
require_once cBASEPATH.'/helpspot/lib/class.userscape.bayesian.classifier.php';
require_once cBASEPATH.'/helpspot/lib/class.language.php';
require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
require_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';
require_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';
require_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
require_once cBASEPATH.'/helpspot/lib/class.license.php';

/*****************************************
CLEAN EXTERNAL DATA
*****************************************/
clean_data();

//Get License
$licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
$GLOBALS['license'] = $licenseObj->getLicense();

/*****************************************
SET VARS
*****************************************/
$page = isset($_GET['pg']) ? $_GET['pg'] : 'home';
$pageid = isset($_POST['xPage']) && is_numeric($_POST['xPage']) ? $_POST['xPage'] : 0; //used in voting logic
$GLOBALS['navOn'] = 'home';

/*****************************************
INCLUDE LANGUAGE
*****************************************/
$GLOBALS['lang'] = new language($page);
include cBASEPATH.'/helpspot/lib/lookup.lib.php';	//include lookups here so we can use lang abstraction

/*****************************************
MULTIPORTAL
*****************************************/
if (defined('cMULTIPORTAL')) {
    include_once cBASEPATH.'/helpspot/lib/class.multiportal.php';
    $GLOBALS['hs_multiportal'] = new hs_multiportal(cMULTIPORTAL);
    $sPortalTerms = utf8_trim($GLOBALS['hs_multiportal']->sPortalTerms);
    $sPortalPrivacy = utf8_trim($GLOBALS['hs_multiportal']->sPortalPrivacy);
} else {
    $sPortalTerms = hs_setting('cHD_PORTAL_TERMS');
    $sPortalPrivacy = hs_setting('cHD_PORTAL_PRIVACY');
}

/*****************************************
CAPTCHA
*****************************************/
if (in_array($page, ['request', 'email'])) {
    if (hs_setting('cHD_PORTAL_CAPTCHA') == 1) {
        //Save previous word from session for use in checking
        session()->put('portal_captcha_lastword', session('portal_captcha'));

        //Get captcha words
        $words = explode("\n", hs_setting('cHD_PORTAL_CAPTCHA_WORDS'));
        //Set word
        $rand = array_rand($words, 1);
        session()->put('portal_captcha', trim($words[$rand]));
    } elseif (hs_setting('cHD_PORTAL_CAPTCHA') == 2 && isset($_POST['g-recaptcha-response'])) {
        include_once cBASEPATH.'/helpspot/lib/recaptcha/recaptchalib.php';
        $recaptcha = new \ReCaptcha(hs_setting('cHD_RECAPTCHA_PRIVATEKEY'));
        // TO DO: Use x-forwarded-for? (Get Request object from Laravel)
        $recaptcha_response = $recaptcha->verifyResponse($_SERVER['REMOTE_ADDR'], $_POST['g-recaptcha-response']);
    }
}

/*****************************************
SWITCH LOGIC
*****************************************/
switch ($page) {
    case 'file':
        // UNUSUAL CASE - including an admin file since all this does is output files
        // We are assuming file.php returns a Response object
        return include cBASEPATH.'/helpspot/pages/file.php';
        break;

    case 'kb.wysiwyg':
        // Show custom wysiwyg style sheet
        return include cBASEPATH.'/helpspot/pages/kb.wysiwyg.php';
        break;

    case 'vote.helpful':
        if (empty($_COOKIE['votehistory'])) {
            $votehistory = [];
        } elseif (strpos($_COOKIE['votehistory'], 'a:0') !== false) {
            $votehistory = unserialize($_COOKIE['votehistory']);
        } else {
            $votehistory = json_decode($_COOKIE['votehistory'], true);
        }

        if (! in_array($pageid, $votehistory)) {
            $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET iHelpful = iHelpful+1 WHERE xPage = ?', [$pageid]);
            $votehistory[] = $pageid;
            setcookie('votehistory', json_encode($votehistory), (time() + 60 * 60 * 24 * 30 * 120));
        }
        return redirect()->to(cHOST.'/index.php?pg=kb.page&id='.$pageid);

        break;

    case 'vote.nothelpful':
        if (empty($_COOKIE['votehistory'])) {
            $votehistory = [];
        } elseif (strpos($_COOKIE['votehistory'], 'a:0') !== false) {
            $votehistory = unserialize($_COOKIE['votehistory']);
        } else {
            $votehistory = json_decode($_COOKIE['votehistory'], true);
        }

        if (! in_array($pageid, $votehistory)) {
            $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET iNotHelpful = iNotHelpful+1 WHERE xPage = ?', [$_POST['xPage']]);
            $votehistory[] = $pageid;
            setcookie('votehistory', json_encode($votehistory), (time() + 60 * 60 * 24 * 30 * 120));
        }
        return redirect()->to(cHOST.'/index.php?pg=kb.page&id='.$pageid);

        break;

    case 'login.create':
        if( session('errors') && session('errors')->any() ) {
            $GLOBALS['errors']['email'] = implode('<br>', session('errors')->get('email'));
            $GLOBALS['errors']['password'] = implode('<br>', session('errors')->get('password'));
        }

        if (isset($_POST['email'])) {
            $c = new HS\Http\Controllers\Auth\Portal\RegisterController();
            return $c->register(request());
        }

        break;
    case 'login.forgot':
        if( session('errors') && session('errors')->any() ) {
            foreach(session('errors')->all() as $error) {
                $GLOBALS['errors']['email'] = $error;
            }
        }
        break;
    case 'login.reset':
        if( session('errors') && session('errors')->any() ) {

            $errors = session('errors');
            if( $errors->has('email') ) $GLOBALS['errors']['email'] = $errors->first('email');
            if( $errors->has('token') ) $GLOBALS['errors']['token'] = $errors->first('token');
            if( $errors->has('password') ) $GLOBALS['errors']['password'] = $errors->first('password');
        }

        if (isset($_POST['email'])) {
            $c = new HS\Http\Controllers\Auth\Portal\ResetPasswordController();
            return $c->reset(request());
        }

        break;
    case 'login':
        if (isset($_POST['login_email']) && isset($_POST['login_password'])) {
            $xLogin = false;
            $email = '';

            // Internal + BlackBox Auth
            $email = trim($_POST['login_email']);

            $authAttempt = auth('portal')->attempt([
                'sEmail' => $email,
                'password' => trim($_POST['login_password'])
            ]);

            if ($authAttempt) {
                $xLogin = auth('portal')->id();
            } else {
                $xLogin = false;
            }

            if ($xLogin) {
                if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE') == 'blackbox') {
                    session()->put('login_username', $_POST['login_email']);
                } //this is the username if we're in black box mode
                session()->put('login_sEmail', $email);
                session()->put('login_xLogin', $xLogin);
                session()->put('login_ip', hs_clientIP());
                //Redirect to history list
                return redirect()->to(cHOST.'/index.php?pg=request.history');
            } else {
                return redirect()->to(cHOST.'/index.php?pg=request.check&login_email='.$_POST['login_email']);
            }
        }

        break;

    case 'logout':

        auth('portal')->logout();
        request()->session()->invalidate();

        return redirect()->to(cHOST.'/index.php');

        break;

    case 'password.change':
        // todo: no longer ajax, make a regular laravel form
        //Make sure they're logged in
        if (! empty($_POST['password']) && session()->has('login_xLogin') && session('login_ip') == hs_clientIP()) {
            $update = apiPortalPasswordUpdate(session('login_sEmail'), $_POST['password']);
            if (! $update) {
                header('HTTP/1.1 400 Bad Request');
            }
        } else {
            header('HTTP/1.1 400 Bad Request');
        }
        exit;

        break;
    case 'request':
        if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE', 'internal') == 'saml') {
            if( ! auth('portal')->check() ) {
                $route = str_replace('/index.php/', '/', route('saml2_login', 'hs'));
                return redirect($route);
            }
        }
        break;
    case 'request.history':
        //Protect the history page
        if (! session()->has('login_xLogin') || session('login_ip') != hs_clientIP()) {
            return redirect()->to(cHOST.'/index.php?pg=request.check&login_email='.session('login_sEmail'));
        }

        break;

    case 'request.check':
        // Auth check if heading to request.check with no id
        if (! isset($_GET['id']) || empty($_GET['id'])) {
            // SAML must login automatically. If auth'ed, head to request.history, else head to auth against SAML
            if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE', 'internal') == 'saml') {
                if( auth('portal')->check() ) {
                    if (! isset($_POST['submit'])) { // Only redirect if not a form submission
                        return redirect()->to(cHOST.'/index.php?pg=request.history');
                    }
                } else {
                    $route = str_replace('/index.php/', '/', route('saml2_login', 'hs'));
                    return redirect($route);
                }
            }
        }

        if (isset($_POST['submit'])) {

            //Don't allow any other post vars than these
            $allowed = ['update', 'doc', 'accesskey'];
            cleanPostArray($allowed);

            require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
            require_once cBASEPATH.'/helpspot/lib/class.triggers.php';
            require_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';
            require_once cBASEPATH.'/helpspot/lib/class.notify.php';

            $pkey = parseAccessKey($_POST['accesskey']);

            $request = apiGetRequest($pkey['xRequest']);

            //Check that it's got the correct password key. If not redirect to empty
            if ($request['sRequestPassword'] == $pkey['sRequestPassword']) {
                if (! empty($_POST['update'])) {

                    //If request was already closed then we should reopen.
                    if (intval($request['fOpen']) === 0) {
                        // Reopen request
                        $request['fOpen'] = 1;
                        $request['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 2);
                        $request['dtGMTOpened'] = date('U');	//current dt
                        //if the user isn't active then send to inbox
                        $ustatus = apiGetUser($request['xPersonAssignedTo']);
                        if ($ustatus['fDeleted'] == 1) {
                            $request['xPersonAssignedTo'] = 0;
                        }

                        $update = new requestUpdate($pkey['xRequest'], $request, 0, __FILE__, __LINE__);
                        $update->notify = false; //notify below instead
                        $reqResult = $update->checkChanges();
                    }

                    //If request was in trash we should remove it
                    if (intval($request['fTrash']) === 1) {
                        // Reopen request
                        $request['fTrash'] = 0;
                        $request['dtGMTTrashed'] = 0;
                        $request['dtGMTOpened'] = date('U');	//current dt

                        $update = new requestUpdate($pkey['xRequest'], $request, 0, __FILE__, __LINE__);
                        $update->notify = false; //notify below instead
                        $reqResult = $update->checkChanges();
                    }

                    $date = date('U');

                    //Special case just for triggers due to how we currently handle portal updates :(
                    $orig_request = apiGetRequest($pkey['xRequest']);
                    $orig_request['acting_person'] = 0;
                    $orig_request['note_type'] = 1;
                    $orig_request['note_content'] = $_POST['update'];
                    $orig_request['xRequest'] = $pkey['xRequest'];

                    apiRunTriggers($pkey['xRequest'], $request, $orig_request, $_POST['update'], 1, 0, 2, __FILE__, __LINE__);

                    $reqHis = apiAddRequestHistory([
                        'xPerson' =>0,
                        'xRequest' => $pkey['xRequest'],
                        'dtGMTChange' => $date,
                        'fPublic' => 1,
                        'tNote' => $_POST['update'],
                    ]);

                    app('events')->flush('request.history.create');

                    /************ ADD DOCUMENT*************/
                    if (! empty($_FILES['doc'])) {
                        foreach ($_FILES['doc']['error'] as $key => $error) {
                            $ext = explode('.', $_FILES['doc']['name'][$key]);
                            $excludedMimeTypes = explode(',', hs_setting('cHD_PORTAL_EXCLUDEMIMETYPES'));

                            if (! empty($_FILES['doc']['name'][$key]) && ! in_array($ext[1], $excludedMimeTypes)) {
                                if ($error == UPLOAD_ERR_OK) {
                                    apiAddDocument(
                                        $pkey['xRequest'],
                                        [
                                            [
                                                'name' => $_FILES['doc']['name'][$key],
                                                'mimetype' => $_FILES['doc']['type'][$key],
                                                'body' => file_get_contents($_FILES['doc']['tmp_name'][$key]),
                                            ],
                                        ],
                                        $reqHis,
                                        __FILE__, __LINE__);
                                } else {
                                    errorLog(hs_imageerror($error), 'Portal File Attachment', $f, $l);
                                }
                            }
                        }
                    }

                    //Send notification from here instead of from within addreqhis so that we can send log and body in one email
                    if (isset($reqHis) || isset($reqResult['xRequestHistory'])) {
                        //Array if both set else just the ID
                        if (isset($reqHis) && isset($reqResult['xRequestHistory'])) {
                            $ids = [$reqHis, $reqResult['xRequestHistory']];	//first is body, second is log
                        } else {
                            $ids = isset($reqHis) ? $reqHis : $reqResult['xRequestHistory'];
                        }

                        $notifier = new hs_notify($pkey['xRequest'], $ids, 0, __FILE__, __LINE__);
                        $notifier->SetRequestType('existing');
                        $notifier->Notify();
                    }
                } else {
                    return redirect()
                        ->to(cHOST.'/index.php?pg=request.check&id='.$pkey['xRequest'].$pkey['sRequestPassword'])
                        ->with('errors-update', lg_portal_req_required);
                }
            } else {
                return redirect()->to(cHOST.'/index.php?pg=request.check');
            }

            //Redirect no matter what
            return redirect()->to(cHOST.'/index.php?pg=request.check&id='.$pkey['xRequest'].$pkey['sRequestPassword']);
        }

        break;
}

//Handle requests special. This allows the creation of multiple request forms all with different page names
if (isset($_POST['simple']) || isset($_POST['did']) || isset($_POST['expected']) || isset($_POST['actual'])) {
    if (isset($_POST['submit'])) {
        $customFields = apiGetCustomFields();

        //Don't allow any other post vars than these
        $allowed = ['did', 'expected', 'actual', 'additional', 'simple', 'fullname', 'sFirstName', 'sLastName', 'required',
            'sUserId', 'sEmail', 'sCC', 'sTitle', 'sPhone', 'fUrgent', 'xCategory', 'hs_fv_timestamp', 'hs_fv_ip', 'hs_fv_hash', 'doc', 'captcha', ];

        //Add custom fields to allowed array
        if (is_array($customFields)) {
            foreach ($customFields as $v) {
                array_push($allowed, 'Custom'.$v['fieldID']);
            }
        }

        cleanPostArray($allowed); //Clean POST to only allowed fields

        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.triggers.php';
        require_once cBASEPATH.'/helpspot/lib/class.notify.php';

        $errors = [];

        //ERROR CHECKS
        //Loop over required fields defined in template
        if (isset($_POST['required']) && ! empty($_POST['required'])) {
            $fields = explode(',', $_POST['required']);

            //Add custom fields to fields to check
            if (is_array($customFields)) {
                foreach ($customFields as $v) {
                    $id = 'Custom'.$v['fieldID'];
                    $customFieldsNumeric[] = $id; //track numeric custom fields for use below

                    if ($v['isRequired'] == 1 && ($v['isAlwaysVisible'] || in_array($v['fieldID'], apiGetCategoryCustomFields($_POST['xCategory'])))) {
                        array_push($fields, $id);
                    }
                    //Special check for numeric fields
                    if (($v['fieldType'] == 'decimal' || $v['fieldType'] == 'numtext') && ! empty($_POST[$id]) && ! is_numeric($_POST[$id])) {
                        $GLOBALS['errors'][$id] = lg_portal_req_numberreq;
                    }
                }
            }

            foreach ($fields as $f) {
                $f = trim($f);
                //Only check if fields are actually set. While this would let someone remove the field from the form to get past it being required
                //the risk seems low and it makes it easier for people to customize the portal as well as allows the custom mods made by multiportal to pass through
                if (isset($_POST[$f]) && empty($_POST[$f])) {
                    //Special check for numeric fields to allow 0's
                    if (in_array($f, $customFieldsNumeric)) {
                        if ((string) $_POST[$f] == '0') {
                            continue;
                        } //0 is OK
                    }
                    $GLOBALS['errors'][$f] = lg_portal_req_required;
                }
            }
        }

        //validate email
        if (! empty($_POST['sEmail']) && ! validateEmail(trim($_POST['sEmail']))) {
            $GLOBALS['errors']['sEmail'] = lg_portal_req_validemail;
        }

        // Check Terms
        if (($sPortalTerms != '' or $sPortalPrivacy != '') and ! $_POST['terms']) {
            $GLOBALS['errors']['terms'] = lg_portal_req_terms;
        }

        //check captcha
        if (hs_setting('cHD_PORTAL_CAPTCHA') == 1) {
            if (strtolower(trim($_POST['captcha'])) != strtolower(trim(session('portal_captcha_lastword')))) {
                $GLOBALS['errors']['captcha'] = lg_portal_er_validcaptcha;
            }
        } elseif (hs_setting('cHD_PORTAL_CAPTCHA') == 2) {
            if (! $recaptcha_response->success) {
                $GLOBALS['errors']['recaptcha'] = lg_portal_er_validrecaptcha;
            }
        }

        //IF NO ERRORS ADD REQUEST
        if (empty($GLOBALS['errors'])) {
            //Combine request detail boxes
            if (hs_setting('cHD_PORTAL_FORMFORMAT') == 1) {
                $_POST['tBody'] = ''.lg_portal_req_did.":\n".$_POST['did'];
                $_POST['tBody'] .= "\n\n".lg_portal_req_expected.":\n".$_POST['expected'];
                $_POST['tBody'] .= "\n\n".lg_portal_req_actual.":\n".$_POST['actual'];
                if (! empty($_POST['additional'])) {
                    $_POST['tBody'] .= "\n\n".lg_portal_req_additional.":\n".$_POST['additional'];
                }
            }

            //allow override if simple passed in
            if (! hs_empty($_POST['simple'])) {
                $_POST['tBody'] = $_POST['simple'];
                if (! empty($_POST['additional'])) {
                    $_POST['tBody'] .= "\n\n".lg_portal_req_additional.":\n".$_POST['additional'];
                }
            }

            //Handle names
            if (isset($_POST['fullname'])) {
                $name = parseName($_POST['fullname']);
                $_POST['sFirstName'] = $name['fname'];
                $_POST['sLastName'] = $name['lname'];
            }

            // Are we allowing custom subjects?
            if (hs_setting('cHD_PORTAL_ALLOWSUBJECT') and trim($_POST['sTitle']) != '') {
                $subject = $_POST['sTitle'];
            } else {
                $subject = lg_portal_subjectdefaultnew;
            }

            // Are we allowing custom CC's?
            $ccAddresses = [];
            if (hs_setting('cHD_PORTAL_ALLOWCC')) {
                $ccAddresses = explode(',', $_POST['sCC']);
                foreach ($ccAddresses as $key => $ccAddress) {
                    $ccAddresses[$key] = utf8_trim($ccAddress);
                    if (! validateEmail($ccAddresses[$key])) {
                        unset($ccAddresses[$key]);
                    }
                }
            }

            // ONly set the headers if we are allowing a custom CC
            if (hs_setting('cHD_PORTAL_ALLOWCC') and $ccAddresses) {
                $msgHeaderTempArray = [
                    'cc' => implode(',', $ccAddresses),
                    'to' => ($mailbox ? $mailbox['sReplyName'] : hs_setting('cHD_NOTIFICATIONEMAILNAME')).' <'.($mailbox ? $mailbox['sReplyEmail'] : hs_setting('cHD_NOTIFICATIONEMAILACCT')).'>',
                    'from' => $name['fname'].' '.$_POST['sLastName'].' <'.$_POST['sEmail'].'>',
                    'sTitle' => $subject,
                    'subject' => $subject,
                ];
                $msgHeaders = hs_serialize($msgHeaderTempArray);
                $_POST['tEmailHeaders'] = $msgHeaders;
            }

            $_POST['mode'] = 'add';
            $_POST['fOpenedVia'] = 7;
            $_POST['fPublic'] = 1;

            //If we're in a remote portal and the send from is set, use it
            if (isset($GLOBALS['hs_multiportal']) && $GLOBALS['hs_multiportal']->xMailboxToSendFrom) {
                if (! function_exists('apiGetMailbox')) {
                    include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
                }
                $mailbox = apiGetMailbox($GLOBALS['hs_multiportal']->xMailboxToSendFrom);

                if ($mailbox) {
                    $_POST['xMailboxToSendFrom'] = $GLOBALS['hs_multiportal']->xMailboxToSendFrom;
                }
            }

            //If we're in a remote portal set the xPortal
            if (isset($GLOBALS['hs_multiportal'])) {
                $_POST['xPortal'] = $GLOBALS['hs_multiportal']->xPortal;
            }

            // Terms and Privacy
            if ($sPortalTerms != '' and $sPortalPrivacy != '') {
                $_POST['terms_description'] = 'User accepted terms and privacy';
            } elseif ($sPortalTerms == '' and $sPortalPrivacy != '') {
                $_POST['terms_description'] = 'User accepted privacy';
            } elseif ($sPortalTerms != '' and $sPortalPrivacy == '') {
                $_POST['terms_description'] = 'User accepted terms';
            }

            //Check for SPAM
            //Note, execution may stop here if the spam autodelete setting is enabled
            apiPortalReqSPAMCheck();

            //Set time here so it can also be used below
            $_POST['dtGMTOpened'] = date('U');

            $reqResult = apiAddEditRequest($_POST, 1, __FILE__, __LINE__);

            if ($reqResult) {

                /************ ADD DOCUMENT*************/
                if (! empty($_FILES['doc'])) {
                    foreach ($_FILES['doc']['error'] as $key => $error) {
                        $ext = explode('.', $_FILES['doc']['name'][$key]);
                        $excludedMimeTypes = explode(',', hs_setting('cHD_PORTAL_EXCLUDEMIMETYPES'));

                        if (! empty($_FILES['doc']['name'][$key]) && ! in_array($ext[1], $excludedMimeTypes)) {
                            if ($error == UPLOAD_ERR_OK) {
                                apiAddDocument(
                                    $reqResult['xRequest'],
                                    [
                                        [
                                            'name'=>$_FILES['doc']['name'][$key],
                                            'mimetype'=>$_FILES['doc']['type'][$key],
                                            'body'=>file_get_contents($_FILES['doc']['tmp_name'][$key]),
                                        ]
                                    ],
                                    $reqResult['xRequestHistory'],
                                    __FILE__, __LINE__);
                            } else {
                                errorLog(hs_imageerror($error), 'Portal File Attachment', $f, $l);
                            }
                        }
                    }
                }

                $email = isset($_POST['sEmail']) && validateEmail($_POST['sEmail']) ? $_POST['sEmail'] : '';

                if ($email && $_POST['xStatus'] != hs_setting('cHD_STATUS_SPAM')) {

                    //Don't send if auto reply is off
                    if (hs_setting('cHD_PORTAL_AUTOREPLY')) {
                        $req = apiGetRequest($reqResult['xRequest']);

                        //Setup where email comes From
                        $assigned_user_id = $req['xPersonAssignedTo'];
                        $from_email = ($mailbox ? $mailbox['sReplyEmail'] : hs_setting('cHD_NOTIFICATIONEMAILACCT'));
                        $from_name = ($mailbox ? $mailbox['sReplyName'] : hs_setting('cHD_NOTIFICATIONEMAILNAME'));

                        $body = stripAdditionalDetails($_POST['tBody']); //remove additional info
                        $body = makeBold(nl2br(hs_htmlspecialchars($body)));

                        $vars = getPlaceholders([
                            'email_subject' => $subject,
                            'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$reqResult['xRequest'].'}',
                            'requestcheckurl' => cHOST.'/index.php?pg=request.check&id='.$reqResult['xRequest'].$reqResult['sRequestPassword'], ], $req);

                        $sendFrom = isset($mailbox)
                            ? new \HS\Mail\SendFrom($mailbox['sReplyEmail'], $mailbox['sReplyName'], $mailbox['xMailbox'])
                            : \HS\Mail\SendFrom::default();

                        $messageBuilder = (new \HS\Mail\Mailer\MessageBuilder($sendFrom, $reqResult['xRequest']))
                            ->to($email)
                            ->cc($ccAddresses)
                            ->setType('public')
                            ->subject('portal_reqcreated', $vars)
                            ->body('portal_reqcreated', $body, $vars);

                        \HS\Jobs\SendMessage::dispatch($messageBuilder, $attachments=null, $publicEmail=true)
                            ->onQueue(config('queue.high_priority_queue')); // mail.public
                    }
                }

                return redirect()->to(cHOST.'/index.php?pg=request.check&id='.$reqResult['xRequest'].$reqResult['sRequestPassword']);
            } else {
                $GLOBALS['errors']['general'] = lg_portal_req_generalerror;
            }
        }
    }
}
