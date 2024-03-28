<?php

use Illuminate\Support\Str;

/**
Represents one email message
*/
class hs_trigger
{
    public $name = '';

    //name of rule
    public $anyall = 'all';

    //match any or all
    public $type = 2;

    //1= created, 2= updated
    public $option_bizhours = '';

    //time rule should be active
    public $option_log = '';

    public $option_no_notifications = '0';

    //suppress notifications
    public $CONDITIONS = [];

    //Array of conditions. Format array([IF]=>"To",[IS]=>"is not",[VALUE]=>"pizza")
    public $ACTIONS = [];	//Action list. Format array([ACTION]=>"optional") ex: array('assign to'=>3)

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Take in the POST array and parse out trigger.
     */
    public function SetTrigger(&$posts)
    {
        $this->name = $posts['sTriggerName'];
        $this->anyall = $posts['anyall'];
        $this->type = $posts['fType'];
        $this->option_bizhours = $posts['option_bizhours'];
        $this->option_log = $posts['option_log'];
        $this->option_no_notifications = $posts['option_no_notifications'];

        foreach ($posts as $k=>$v) {
            //Pass in first values, the other method will pull rest out of posts array
            if (strpos($k, 'condition') !== false && strpos($k, '_1') && ! empty($posts[$k])) {
                $this->CONDITIONS[] = $this->setCondition($k, $v, $posts);
            }
            if (strpos($k, 'action') !== false && strpos($k, '_1') && ! empty($posts[$k])) {
                $this->ACTIONS[] = $this->setAction($k, $v, $posts);
            }
        }
    }

    /**
     * Parses conditions and returns properly formatted array.
     */
    public function setCondition($key, $value, &$posts)
    {
        $_2ndfield = str_replace('_1', '_2', $key);
        $_3rdfield = str_replace('_1', '_3', $key);

        switch ($value) {
            case 'note_content':
            case 'acting_person':
            case 'note_type':
            case 'sUserId':
            case 'sEmail':
            case 'sFirstName':
            case 'sLastName':
            case 'sPhone':
            case 'xOpenedViaId':
            case 'fOpenedVia':
            case 'xPortal':
            case 'xStatus':
            case 'xPersonAssignedTo':
            case 'xCategory':
            case 'fOpen':
            case 'reportingTags':
            case 'fUrgent':
                return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>trim($posts[$_3rdfield])];

                break;
        }

        //Custom fields
        if (isset($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                $fid = 'Custom'.$fvalue['fieldID'];
                if ($value == $fid) {
                    switch ($fvalue['fieldType']) {
                        case 'checkbox':
                        case 'lrgtext':
                        case 'numtext':
                        case 'text':
                        case 'drilldown':
                        case 'date':
                        case 'datetime':
                        case 'regex':
                        case 'ajax':
                        case 'decimal':
                        case 'select':
                            return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>trim($posts[$_3rdfield])];

                            break;
                    }
                }
            }
        }
    }

    /**
     * Parses actions and returns properly formatted array.
     */
    public function setAction($key, $value, &$posts)
    {
        $_2ndfield = str_replace('_1', '_2', $key);
        $_3rdfield = str_replace('_1', '_3', $key);
        $_4thfield = str_replace('_1', '_4', $key);
        $_5thfield = str_replace('_1', '_5', $key);

        switch ($value) {
            case 'auto_notify':
            case 'auto_notifysms':
            case 'auto_notifyexternal':
            case 'auto_emailcustomer':
                return [$value=>['staffmember'=>$posts[$_5thfield],
                                           'mailbox'	=>$posts[$_3rdfield],
                                           'subject'	=>$posts[$_2ndfield],
                                           'email'		=>$posts[$_4thfield], ]];

                break;

            case 'auto_emailresults':
                return [$value=>$posts[$_2ndfield]];

                break;

            case 'subscribe_staff':
                return [$value=>$posts[$_2ndfield]];

                break;
            case 'unsubscribe_staff':
                return [$value=>$posts[$_2ndfield]];

                break;

            case 'setcategory':
                $out = ['setcategory'=>$posts[$_2ndfield]];
                $out['assign_to'] = $posts[$_3rdfield];

                return $out;

                break;

            case 'setreptags':
                $out = ['setreptags'=>[]];
                $out['setreptags']['xCategory'] = $posts[$_2ndfield];
                $out['setreptags']['reportingTags'] = $posts[$_3rdfield];

                return $out;

                break;

            case 'request_push':
                return [$value => ['push_option'=>$posts[$_2ndfield], 'tComment'=>$posts[$_3rdfield]]];

                break;

            case 'setcustomfield':
                //Find all custom fields
                $fields = ['setcustomfield'=>[]];
                foreach ($GLOBALS['customFields'] as $v) {
                    $custid = 'Custom'.$v['fieldID'];
                    if (isset($posts[$custid]) && ! empty($posts[$custid])) {
                        if ($v['fieldType'] == 'date') {
                            $fieldValue = jsDateToTime(trim($posts[$custid]), hs_setting('cHD_POPUPCALSHORTDATEFORMAT'));
                            $fields['setcustomfield'][$custid] = $fieldValue;
                        } elseif ($v['fieldType'] == 'datetime') {
                            $fieldValue = jsDateToTime(trim($posts[$custid]), hs_setting('cHD_POPUPCALDATEFORMAT'));
                            $fields['setcustomfield'][$custid] = $fieldValue;
                        } else {
                            $fields['setcustomfield'][$custid] = $posts[$custid];
                        }
                    }
                }

                return $fields;

                break;

            case 'close':
            case 'open':
            case 'setstatus':
            case 'notify':
            case 'instantreply':
            case 'addprivnote':
            case 'live_lookup':
            case "webhook":
			case "thermostat_send":
			case "thermostat_add_email":
				return array($value=>$posts[$_2ndfield]);
				break;

            case 'markurgent':
                return ['markurgent'=>true];

                break;

            case 'marknoturgent':
                return ['marknoturgent'=>true];

                break;

            case 'movetotrash':
                return ['movetotrash'=>true];

                break;

            case 'movetoinbox':
                return ['movetoinbox'=>true];

                break;
        }
    }

    /*
    Compare the two requests and return if the trigger should be triggered
    */
    public function compareRequestsAndTrigger($new, $old = false)
    {
        $trigger = false;

        //Biz hour logic. If set to only do biz hours and we're not in them or set to off hours only and not in them then return false
        if (! isset($GLOBALS['bizhours'])) {
            include_once cBASEPATH.'/helpspot/lib/class.business_hours.php';
            $GLOBALS['bizhours'] = new business_hours;
        }
        if (($this->option_bizhours == 'bizhours' && ! $GLOBALS['bizhours']->inBizHours(time())) || ($this->option_bizhours == 'offhours' && $GLOBALS['bizhours']->inBizHours(time()))) {
            return $new;
        }

        //If there's no reporting tags in the old request then they were never changed so the current ones are still valid
        if ($old && ! isset($old['reportingTags'])) {
            $old['reportingTags'] = $new['reportingTags'];
        }

        foreach ($this->CONDITIONS as $k=>$v) {
            if ($v['IF'] == 'note_content') {
                $new[$v['IF']] = strip_tags($new[$v['IF']]);
                if ($old) { // ensure we actually have $old data before parsing.
                    $old[$v['IF']] = strip_tags($old[$v['IF']]);
                }
            }

            // If we are checking against loggedin user be sure we have
            // a valid user. This will be an empty string when ran through tasks
            // which means it'll match the inbox. So if we don't have a user bail out.
            if ($v['VALUE'] === 'loggedin') {
                $user = apiGetLoggedInUser();
                if ($user['xPerson'] > 0) {
                    $v['VALUE'] = $user['xPerson'];
                }
            }

            if ($old) {
                $trigger = $this->_compare($v['IS'], $v['VALUE'], $new[$v['IF']], $old[$v['IF']]);
            } else {
                $trigger = $this->_compare($v['IS'], $v['VALUE'], $new[$v['IF']]);
            }

            if ($this->anyall == 'any') {
                if ($trigger == true) {
                    break;
                } //break when we find one true one
            } elseif ($this->anyall == 'all') {
                if ($trigger == false) {
                    break;
                } //break on any falses
            }
        }

        //Apply trigger
        if ($trigger) {
            return $this->ApplyTrigger($new);
        }

        return $new;
    }

    /*
    Compare old and new values
    */
    public function _compare($op, $o_value, $o_new, $o_old = '')
    {
        $value = $o_value;
        $new = $o_new;
        $old = $o_old;

        //Make case insensitive (preg still uses original)
        if (is_string($o_value)) {
            $value = utf8_strtolower($o_value);
        }
        if (is_string($o_new)) {
            $new = utf8_strtolower($o_new);
        }
        if (is_string($o_old)) {
            $old = utf8_strtolower($o_old);
        }

        switch ($op) {
            case 'is':
                return $value == $new;

                break;
            case 'is_not':
                return $value != $new;

                break;
            case 'begins_with':
                return $value == utf8_substr($new, 0, utf8_strlen($value)) ? true : false;

                break;
            case 'ends_with':
                $len = utf8_strlen($value);

                return $value == utf8_substr($new, -$len, $len) ? true : false;

                break;
            case 'contains':
                return utf8_strpos($new, $value) === false ? false : true; //note opposite order of others
                break;
            case 'not_contain':
                return utf8_strpos($new, $value) === false ? true : false;

                break;
            case 'matches':
                return preg_match($o_value, $o_new);

                break;
            case 'changed':
                return $new != $old;

                break;
            case 'changed_to':
                return $new != $old && $value == $new;

                break;
            case 'changed_from':
                return $new != $old && $value == $old;

                break;
            case 'not_changed':
                return $new == $old;

                break;
            case 'not_changed_to':
                return $new != $old && $value != $new;

                break;
            case 'not_changed_from':
                return $new != $old && $value != $old;

                break;
            //Reporting tag specific operators
            case 'rt_is_selected':
                return in_array($value, $o_new);

                break;
            case 'rt_is_not_selected':
                return ! in_array($value, $o_new);

                break;
            case 'rt_was_selected':
                return in_array($value, $o_old);

                break;
            case 'rt_was_not_selected':
                return ! in_array($value, $o_old);

                break;
        }
    }

    /**
     * Apply rules to the pased in message object. Should be of type defined in class.imap.message.php
     * $msg is an imap.message object
     * $mailbox is an array of mailbox info.
     */
    public function ApplyTrigger($req)
    {

        //Log notes
        $lognotes = '';

        $req['dtGMTOpened'] = time() + 1;	//Open field is used by requestcheck to set change time.
        //Actions
        foreach ($this->ACTIONS as $key=>$valarray) {
            foreach ($valarray as $k=>$v) {
                switch ($k) {
                    case 'setcategory':
                        $req['xCategory'] = $v;

                        break;

                    case 'assign_to':
                        // Be sure the staff is active:
                        $staffList = apiGetAllUsers();
                        $staffList = rsToArray($staffList, 'xPerson', false);
                        $req['xPersonAssignedTo'] = 0;
                        foreach ($staffList as $staff) {
                            if ($staff['xPerson'] == $v) {
                                $req['xPersonAssignedTo'] = $v;
                            }
                        }

                        break;

                    case 'reportingTags':
                        $req['reportingTags'] = $v;

                        break;

                    case 'setreptags':
                        $req['xCategory'] = $v['xCategory'];
                        $req['reportingTags'] = $v['reportingTags'];

                        break;

                    case 'setcustomfield':
                        foreach ($v as $field=>$value) {
                            $req[$field] = $value;
                        }

                        break;

                    case 'close':
                        $req['fOpen'] = 0;
                        $req['xStatus'] = $v;

                        break;

                    case 'open':
                        $req['fOpen'] = 1;
                        $req['xStatus'] = $v;

                        break;

                    case 'setstatus':
                        $req['xStatus'] = $v;
                        //If it's spam set the assigned user to inbox
                        if ($v == hs_setting('cHD_STATUS_SPAM', 2)) {
                            $req['xPersonAssignedTo'] = 0;
                        }

                        break;

                    case 'addprivnote':
                        $reqHis = apiAddRequestHistory([
                            'xRequest' => $req['xRequest'],
                            'xPerson' => -1,
                            'dtGMTChange' => date('U') + 2, //make sure it's at top of history view
                            'fPublic' => 0,
                            'fNoteIsHTML' => (hs_setting('cHD_HTMLEMAILS')) ? 1 : 0,
                            'tLog' => '',
                            'tNote' => (hs_setting('cHD_HTMLEMAILS')) ? hs_markdown($v) : $v,
                        ]);

                        app('events')->flush('request.history.create');

                        break;

                    case 'auto_notify':
                    case 'auto_notifysms':
                    case 'auto_notifyexternal':
                    case 'auto_emailcustomer':
                        $reqcheckurl = action('Admin\AdminBaseController@adminFileCalled', [
                            'pg' => 'request',
                            'reqid' => $req['xRequest'],
                        ]);
                        $do_notify = true;

                        if ($k == 'auto_notify') {
                            $v['staffmember'] = ($v['staffmember'] == 'assigneduser') ? $req['xPersonAssignedTo'] : $v['staffmember'];
                            $user = apiGetUser($v['staffmember']);
                            if (! $user || userIsDeleted($user)) { // if the user is deleted bail out
                                break;
                            }
                            $email = $user['sEmail'];
                            $email2 = $user['fNotifyEmail2'] ? $user['sEmail2'] : false;
                            $email_template = 'staff';
                        } elseif ($k == 'auto_notifyexternal') {
                            $email = $v['staffmember'];
                            // Is this a custom field they want to use?
                            if (Str::startsWith($email, '##CUSTOM')) {
                                $field = str_replace('##', '', $email);
                                $field = ucfirst(strtolower($field)); // Convert "CUSTOM" to "Custom" so it matches the $req key.
                                $email = $req[$field];
                            }
                            $email_template = 'external';
                        } elseif ($k == 'auto_notifysms') {
                            $v['staffmember'] = ($v['staffmember'] == 'assigneduser') ? $req['xPersonAssignedTo'] : $v['staffmember'];
                            $user = apiGetUser($v['staffmember']);
                            if (! $user || userIsDeleted($user)) { // if the user is deleted bail out
                                break;
                            }
                            $sms = apiGetSMS($user['xSMSService']);
                            $email = $user['sSMS'].'@'.$sms['sAddress'];
                            $email_template = 'sms';
                            $forcetext = true;
                        } else {
                            $email = $req['sEmail'];
                            $email2 = false;
                            $email_template = 'public';
                            $reqcheckurl = cHOST.'/index.php?pg=request.check&id='.$req['xRequest'].$req['sRequestPassword'];
                        }

                        // Verify it's actually an email or bail.
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $do_notify = false;
                            errorLog('* Request:'.$req['xRequest'].': '.$email.' is not a valid email.', 'Trigger Send to External email failed', __FILE__, __LINE__);
                        }

                        if (! empty($email) && $do_notify) {
                            if ($v['mailbox'] == 'frommailbox') {
                                if (! function_exists('apiGetMailbox')) {
                                    include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
                                }
                                $mailbox = apiGetMailbox($req['xMailboxToSendFrom']);
                                $em[0] = $mailbox['sReplyName'] ? replyNameReplace($mailbox['sReplyName'], $req['xPersonAssignedTo']) : hs_setting('cHD_NOTIFICATIONEMAILNAME');
                                $em[1] = $mailbox['sReplyEmail'] ? $mailbox['sReplyEmail'] : hs_setting('cHD_NOTIFICATIONEMAILACCT');
                                $em[2] = $mailbox['xMailbox'] ? $mailbox['xMailbox'] : 0;
                                $sendFrom = new \HS\Mail\SendFrom($em[1], $em[0], $em[2]);
                            } else {
                                $sendFrom =\HS\Mail\SendFrom::fromRequestForm($v['mailbox'], $req['xPersonAssignedTo']);
                            }

                            $tos = [$email];
                            if ($email2) {
                                $tos[] = $email2;
                            }

                            //Do placeholder replacement. If sending SMS then truncate, if not send through markdown
                            // $v['email'] = tokenReplace($v['email'], getPlaceholders([], $req)); # TODO: I don't believe we need this any longer

                            if ($k == 'auto_notifysms') {
                                $v['email'] = utf8_substr($v['email'], 0, ($sms['sMsgSize'] - 10));
                            } else {
                                $v['original_markdown_text'] = $v['email'];

                                // Does it contain HTML? If so don't markdown it. The reason for this is if you
                                // have {{ $initialrequest }} that contains html it could get double encoded
                                // or turned into code blocks because of the spacing in the string.
                                $v['email'] = $this->autoNotifyMarkdown($v['email']);
                            }

                            //Setup vars. Need to setup for each possible type public, staff, external, sms
                            $var_setup = [
                                'email_subject' => Facades\HS\View\Mail\TemplateParser::templateString($v['subject'], getPlaceholders([], $req)),
                                'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$req['xRequest'].'}',
                                'requestcheckurl' => $reqcheckurl,
                            ];

                            if ($k == 'auto_notify') {
                                $allStaff = apiGetAllUsersComplete();
                                $cats = apiGetAllCategoriesComplete();
                                $catlist = [];
                                while ($cat = $cats->FetchRow()) {
                                    $catlist[$cat['xCategory']] = $cat['sCategory'];
                                }
                                $catlist[0] = lg_inbox;
                                $var_setup = array_merge($var_setup, [
                                    'label' => lg_feed_update,
                                    'subject' => $v['subject'],
                                    'requestdetails' => renderRequestTextHeader($req, $allStaff, $catlist),
                                    'requestdetails_html' => renderRequestTextHeader($req, $allStaff, $catlist, 'html'),
                                ]);
                            }

                            $vars = getPlaceholders($var_setup, $req);

                            if ($k == 'auto_emailcustomer') {
                                //Add a request history item with body of email sent to customer/staffer
                                $reqHis = apiAddRequestHistory([
                                    'xRequest' => $req['xRequest'],
                                    'xPerson' => -1,
                                    'dtGMTChange' => date('U') + 2, //make sure it's at top of history view
                                    'fPublic' => 1,
                                    'fNoteIsHTML' => (hs_setting('cHD_HTMLEMAILS')) ? 1 : 0,
                                    'tLog' => serialize(['customeremail' => $email, 'sTitle' => $v['subject']]),
                                    'tNote' => buildNoteBody($v['email'], $vars),
                                ]);

                                app('events')->flush('request.history.create');
                            }

                            $message = (new \HS\Mail\Mailer\MessageBuilder($sendFrom, $req['xRequest']))
                                ->to($tos)
                                ->setType('public')
                                ->subject($email_template, $vars)
                                ->body($email_template, $v['email'], $vars);

                            \HS\Jobs\SendMessage::dispatch($message, $attachments=null, $publicEmail=true)
                                ->onQueue(config('queue.high_priority_queue')); // mail.public

                            if ($k == 'auto_notify') {
                                //Log notifiction email
                                $lognotes .= "\n".lg_notified.": {$user['sFname']} {$user['sLname']}";
                            } elseif ($k == 'auto_notifysms') {
                                //Log notifiction email
                                $lognotes .= "\n".lg_notifiedsms.": {$user['sFname']} {$user['sLname']}";
                            } elseif ($k == 'auto_notifyexternal') {
                                $notified = $v['staffmember'];
                                if (strpos($v['staffmember'], '##Custom') !== false) {
                                    $notified = $email;
                                }
                                $lognotes .= "\n".lg_notified.": {$notified}";
                            }
                        }

                        break;

                    case 'request_push':
                            ob_start(); //don't allow any output
                            $result = doRequestPush($req['xRequest'], $v['push_option'], $v['tComment']);
                            ob_clean();

                            if ($result['isobject']) {
                                if (! empty($result['errors'])) {
                                    $lognotes .= "\n".lg_at_reqpusherror1.' '.$result['errors'];
                                }
                            } else {
                                $lognotes .= "\n".lg_at_reqpusherror2;
                            }

                        break;

                    case 'live_lookup':
                        if (! function_exists('apiLiveLookup')) {
                            include_once cBASEPATH.'/helpspot/lib/livelookup.php';
                        }
                        $ll_sources = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'));
                        //Find right source
                        $i = 0;
                        foreach ($ll_sources as $k=>$source) {
                            if ($source['name'] == $v) {
                                $sourceid = $i;

                                break;
                            }
                            $i++;
                        }

                        //Setup data to pass to LL
                        $req['source_id'] = $sourceid;
                        $req['customer_id'] = isset($req['sUserId']) ? $req['sUserId'] : '';
                        $req['first_name'] = isset($req['sFirstName']) ? $req['sFirstName'] : '';
                        $req['last_name'] = isset($req['sLastName']) ? $req['sLastName'] : '';
                        $req['email'] = isset($req['sEmail']) ? $req['sEmail'] : '';
                        $req['phone'] = isset($req['sPhone']) ? $req['sPhone'] : '';

                        $result = apiLiveLookup($req, 'raw');

                        //Check we get an array and check that there is only 1 result, disregard if more than 1
                        if ($result && is_array($result) && count($result) == 1) {
                            //Set fields
                            if ($result[0]['customer_id']) {
                                $req['sUserId'] = trim($result[0]['customer_id']);
                            }
                            if ($result[0]['first_name']) {
                                $req['sFirstName'] = trim($result[0]['first_name']);
                            }
                            if ($result[0]['last_name']) {
                                $req['sLastName'] = trim($result[0]['last_name']);
                            }
                            if ($result[0]['email']) {
                                $req['sEmail'] = trim($result[0]['email']);
                            }
                            if ($result[0]['phone']) {
                                $req['sPhone'] = trim($result[0]['phone']);
                            }

                            if (isset($GLOBALS['customFields'])) {
                                foreach ($GLOBALS['customFields'] as $k=>$v) {
                                    if (isset($result[0]['Custom'.$k])) {
                                        $req['Custom'.$k] = trim($result[0]['Custom'.$k]);
                                    }
                                }
                            }
                        }

                        // Insert results
                        break;

                    case 'auto_emailresults':
                        $email_reports_to[] = $v;

                        break;

                    case 'markurgent':
                        $req['fUrgent'] = 1;

                        break;

                    case 'marknoturgent':
                        $req['fUrgent'] = 0;

                        break;

                    case 'movetotrash':
                        $req['fTrash'] = 1;
                        $req['dtGMTTrashed'] = date('U');

                        break;

                    case 'movetoinbox':
                        $req['xPersonAssignedTo'] = 0;

                        break;

                    case 'webhook':

                        //Add in request history
                        $req['request_history'] = ['item'=>hs_clean_req_history_for_API($req['xRequest'], $req, 0)];

                        hsPost($v, $req);

                        break;

                    case 'thermostat_send':
                        include_once cBASEPATH.'/helpspot/lib/api.thermostat.lib.php';
                        sendThermostatSurvey($req, $v);

                        break;

                    case 'thermostat_add_email':
                        include_once cBASEPATH.'/helpspot/lib/api.thermostat.lib.php';
                        addThermostatEmail($req, $v);

                        break;

                    case 'subscribe_staff':
                        apiSubscribeToRequest($req['xRequest'], $v);

                        break;

                    case 'unsubscribe_staff':
                        apiUnsubscribeToRequest($req['xRequest'], $v);

                        break;
                }
            }
        }

        if (! class_exists('requestUpdate')) {
            include_once cBASEPATH.'/helpspot/lib/class.requestUpdate.php';
        }

        //Save action changes
        $update = new requestUpdate($req['xRequest'], $req, -1, __FILE__, __LINE__);
        $update->skipTrigger = true;
        if ($this->option_log != 'false' || $this->option_log == null) {
            $update->log_heading = lg_trigger.': '.$this->name;
            $update->logNote = ($lognotes ? $lognotes : ''); //triggers should always add a log as we don't reach this point without a match
        }
        $update->notify = ($this->option_no_notifications ? 0 : 1); //Do or don't do notifications
        $reqResult = $update->checkChanges();

        return $req;
    }

    public function autoNotifyMarkdown($email)
    {
        $email = implode("\n", array_map('trim', explode("\n", $email)));

        return hs_markdown($email);
    }
}
