<?php

use HS\Mail\SendFrom;
use HS\Jobs\SendMessage;
use HS\IncomingMail\Message;
use HS\Mail\Mailer\MessageBuilder;
use HS\IncomingMail\Loggers\EchoLogger;

/**
Represents one email message
 */
class hs_mail_rule
{
    public $name = '';

    //name of rule
    public $anyall = '';

    //match any or all
    public $option_bizhours = '';

    //time rule should be active
    public $CONDITIONS = [];

    //Array of conditions. Format array([IF]=>"To",[IS]=>"is not",[VALUE]=>"pizza")
    public $ACTIONS = [];	//Action list. Format array([ACTION]=>"optional") ex: array('assign to'=>3)

    /**
     * Constructor.
     */
    public function __construct()
    {
        //Call to get rules
        //Init rules
    }

    /**
     * Take in the POST array and parse out mail rule.
     */
    public function SetMailRule(&$posts)
    {
        $this->name = $posts['sRuleName'];
        $this->anyall = $posts['anyall'];
        $this->option_bizhours = $posts['option_bizhours'];

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
            case 'to':
            case 'from':
            case 'cc':
            case 'subject':
            case 'headers':
            case 'email_body':
            case 'customer_id':
                return ['IF'=>$value, 'IS'=>$posts[$_2ndfield], 'VALUE'=>$posts[$_3rdfield]];

                break;

            case 'mailbox_id':
                return ['IF'=>$value, 'VALUE'=>$posts[$_2ndfield]];

                break;

            case 'has_attach':
            case 'is_urgent':
            case 'is_spam':
            case 'is_not_spam':
                return ['IF'=>$value];

                break;
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
            case 'setstatus':
            case 'notify':
            case 'instantreply':
            case 'subscribe_staff':
            case 'unsubscribe_staff':
            case 'addprivnote':
                return [$value=>$posts[$_2ndfield]];

                break;

            case 'auto_notifysms':
            case 'auto_notifyexternal':
                return [$value=>['staffmember'=>$posts[$_5thfield],
                    'mailbox'	=>$posts[$_3rdfield],
                    'subject'	=>$posts[$_2ndfield],
                    'email'		=>$posts[$_4thfield], ]];

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
        }
    }

    /**
     * Apply rules to the passed in message object.
     *
     * @param $reqid
     * @param $reqHisId
     * @param Message $message
     * @param array $mailbox
     * @param $spam
     * @param EchoLogger $logger
     * @param string $customerid
     * @return bool
     */
    public function ApplyRule($reqid, $reqHisId, Message $message, array $mailbox, $spam, $logger, $customerid = '')
    {
        $stack = false;
        $lognotes = '';

        // Init the biz hours if it's not started
        if (! isset($GLOBALS['bizhours'])) {
            $GLOBALS['bizhours'] = new business_hours;
        }

        //Biz hour logic. If set to only do biz hours and we're not in them or set to off hours only and not in them then return false
        if (($this->option_bizhours == 'bizhours' && ! $GLOBALS['bizhours']->inBizHours(time())) || ($this->option_bizhours == 'offhours' && $GLOBALS['bizhours']->inBizHours(time()))) {
            return false;
        }

        if ($logger) {
            echo ">>>> Mail Rule: {$this->name}\n";
        }

        if (count($this->CONDITIONS) == 0) {
            $logger->display('Mail Rule Failed: No conditions');

            return false;
        }

        //Conditional checks
        foreach ($this->CONDITIONS as $k=>$v) {
            switch ($v['IF']) {
                case 'to':
                case 'from':
                case 'cc':
                case 'subject':
                    $match = $this->doMatch($v['IS'], $v['VALUE'], $message->getHeaderByKey($v['IF']));	//IF name same as header for these
                    break;
                case 'headers':
                    $match = $this->doMatch($v['IS'], $v['VALUE'], $message->headers);

                    break;
                case 'email_body':
                    //Strip HTML out from email_body so we don't search
                    // inside of <html tags> for matches
                    $messageBody = strip_tags($message->getParsedBody());
                    $match = $this->doMatch($v['IS'], $v['VALUE'], $messageBody);

                    break;
                case 'customer_id':
                    $match = $this->doMatch($v['IS'], $v['VALUE'], $customerid);

                    break;

                case 'mailbox_id':
                    $match = $this->doMatch('is', $v['VALUE'], $mailbox['xMailbox']);	//only does an equal check
                    break;

                case 'has_attach':
                    $count = count($message->inline_attachments) + count($message->attachments);
                    $match = $count > 0 ? true : false;

                    break;

                case 'is_urgent':
                    $match = $message->isImportant();

                    break;
                case 'is_spam':
                    $match = $spam;

                    break;
                case 'is_not_spam':
                    $match = ! $spam;

                    break;
            }

            //Debug output
            $logger->display("Mail Rule Condition: {$v['IF']} | {$v['IS']} | {$v['VALUE']}: -> ".($match ? 'true' : 'false'));

            //If all should match and one doesn't then abort further checks
            if ($this->anyall == 'all' && ! $match) {
                $logger->display('Mail Rule Failed ALL Condition');

                return false;
            }

            //Used for any's. This var will be true if any of the conditions have been true
            $stack = $match ? true : false;

            //if any should match then abort loop at first true and continue to actions
            if ($this->anyall == 'any' && $match) {
                break;
            }
        }

        //All is checked above since it aborts if any false are found
        //Here we check the any's to make sure there's at least one true
        if ($this->anyall == 'any' && $stack == false) {
            $logger->display('Mail Rule Failed ANY Condition');

            return false;
        }

        //Get inserted req
        $req = apiGetRequest($reqid);
        $req['dtGMTOpened'] = $req['dtGMTOpened'] + 1;	//Add one second so it appears after orig req created

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

                    case 'setstatus':
                        $req['xStatus'] = $v;
                        if ($v == hs_setting('cHD_STATUS_SPAM', 2)) {
                            $req['xPersonAssignedTo'] = 0;
                        } //if spam then set assigned user to 0
                        break;

                    case 'addprivnote':
                        $reqHis = apiAddRequestHistory([
                            'xRequest' => $reqid,
                            'xPerson' => -1,
                            'dtGMTChange' => date('U') + 2, //make sure it's at top of history view
                            'fPublic' => 0,
                            'fNoteIsHTML' => (hs_setting('cHD_HTMLEMAILS') ? 1 : 0),
                            'tLog' => '',
                            'tNote' => (hs_setting('cHD_HTMLEMAILS')) ? hs_markdown($v) : $v,
                        ]);

                        break;

                    case 'notify':
                        $user = apiGetUser($v);
                        if (! $user || userIsDeleted($user)) { // if the user is deleted bail out.
                            break;
                        }

                        if (! isset($notifier)) {
                            $notifier = new hs_notify($reqid, $reqHisId, -1, __FILE__, __LINE__);
                            $notifier->SetRequestType('mailrule');
                        }

                        $notifier->AddToNotifyQueue($v);

                        break;

                    case 'subscribe_staff':
                        apiSubscribeToRequest($req['xRequest'], $v);

                        break;

                    case 'unsubscribe_staff':
                        apiUnsubscribeToRequest($req['xRequest'], $v);

                        break;

                    case 'instantreply':
                        // $v = tokenReplace($v, getPlaceholders([], $req)); # TODO: I dont think this is required any longer
                        $v['original_markdown_text'] = $v['email'];
                        $v = hs_markdown($v);

                        //Build message body
                        $vars = getPlaceholders([
                            'email_subject' => apiSetSubjectPrefix(lg_mailre, $req['sTitle']),
                            'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$reqid.'}',
                            'requestcheckurl' => cHOST.'/index.php?pg=request.check&id='.$req['xRequest'].$req['sRequestPassword'],
                        ], $req);

                        $sendFrom = new SendFrom($mailbox['sReplyEmail'], replyNameReplace($mailbox['sReplyName'], $req['xPersonAssignedTo']), $mailbox['xMailbox']);
                        $messageBuilder = (new MessageBuilder($sendFrom, $reqid))
                            ->to($req['sEmail'])
                            ->setType('public')
                            ->subject('public', $vars)
                            ->body('public', $v, $vars);

                        SendMessage::dispatch($messageBuilder, $attachments=null, $publicEmail=true)
                            ->onQueue(config('queue.high_priority_queue')); // mail.public

                        //Add a request history item with body of email sent
                        $reqHis = apiAddRequestHistory([
                            'xRequest' => $reqid,
                            'xPerson' => -1,
                            'dtGMTChange' => date('U') + 2, //make sure it's at top of history view
                            'fPublic' => 1,
                            'fNoteIsHTML' => (hs_setting('cHD_HTMLEMAILS')) ? 1 : 0,
                            'tLog' => '',
                            'tNote' => $v,
                        ]);

                        break;

                    case 'auto_notifysms':
                    case 'auto_notifyexternal':
                        $reqcheckurl = action('Admin\AdminBaseController@adminFileCalled', [
                            'pg' => 'request',
                            'reqid' => $req['xRequest'],
                        ]);

                        if ($k == 'auto_notifyexternal') {
                            $email = $v['staffmember'];
                            $email_template = 'external';
                        } elseif ($k == 'auto_notifysms') {
                            $v['staffmember'] = ($v['staffmember'] == 'assigneduser') ? $req['xPersonAssignedTo'] : $v['staffmember'];
                            $user = apiGetUser($v['staffmember']);
                            $sms = apiGetSMS($user['xSMSService']);
                            $email = $user['sSMS'].'@'.$sms['sAddress'];
                            $email_template = 'sms';
                        } else {
                            $email = $req['sEmail'];
                            $email2 = false;
                            $email_template = 'public';
                            $reqcheckurl = cHOST.'/index.php?pg=request.check&id='.$req['xRequest'].$req['sRequestPassword'];
                        }
                        if (! empty($email)) {
                            if ($v['mailbox'] == 'frommailbox') {
                                $mailbox = apiGetMailbox($req['xMailboxToSendFrom']);
                                $em[0] = $mailbox['sReplyName'] ? replyNameReplace($mailbox['sReplyName'], $req['xPersonAssignedTo']) : hs_setting('cHD_NOTIFICATIONEMAILNAME');
                                $em[1] = $mailbox['sReplyEmail'] ? $mailbox['sReplyEmail'] : hs_setting('cHD_NOTIFICATIONEMAILACCT');
                                $em[2] = $mailbox['xMailbox'] ? $mailbox['xMailbox'] : 0;
                                $sendFrom = new SendFrom($em[1], $em[0], $em[2]);
                            } else {
                                $sendFrom = SendFrom::fromRequestForm($v['mailbox'], $req['xPersonAssignedTo']);
                            }

                            $tos = [$email];
                            if ($email2) {
                                $tos[] = $email2;
                            }

                            //Do placeholder replacement. If sending SMS then truncate, if not send through markdown
                            // $v['email'] = tokenReplace($v['email'], getPlaceholders([], $req)); # TODO: I don't think this is required any longer
                            if ($k == 'auto_notifysms') {
                                $v['email'] = substr($v['email'], 0, ($sms['sMsgSize'] - 10));
                            } else {
                                $v['original_markdown_text'] = $v['email'];
                                $v['email'] = hs_markdown($v['email']);
                            }

                            //Setup vars. Need to setup for each possible type public, staff, external, sms
                            $var_setup = [
                                'email_subject' => $v['subject'],
                                'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$req['xRequest'].'}',
                                'requestcheckurl' => $reqcheckurl,
                            ];

                            $vars = getPlaceholders($var_setup, $req);

                            $messageBuilder = (new MessageBuilder($sendFrom, $reqid))
                                ->to($tos)
                                ->setType('public')
                                ->subject($email_template, $vars)
                                ->body($email_template, $v['email'], $vars);

                            SendMessage::dispatch($messageBuilder, $attachments=null, $publicEmail=true)
                                ->onQueue(config('queue.high_priority_queue')); // mail.public

                            if ($k == 'auto_notifysms') {
                                //Log notifiction email
                                $lognotes .= "\n".lg_notifiedsms.": {$user['sFname']} {$user['sLname']}";
                            } elseif ($k == 'auto_notifyexternal') {
                                $lognotes .= "\n".lg_notified.": {$v['staffmember']}";
                            }
                        }

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
                }
            }
        }

        //Save action changes
        $update = new requestUpdate($reqid, $req, -1, __FILE__, __LINE__);
        $update->log_heading = lg_mailrule.': '.$this->name;
        $update->logNote = $lognotes;
        $reqResult = $update->checkChanges();

        //Do notifications if any
        if (isset($notifier)) {
            $notifier->Notify();
        }
    }

    /**
     * Does the different types of IS checks.
     */
    public function doMatch($type, $o_search, $o_string)
    {
        //Make case insensitive (preg still uses original)
        $search = strtolower($o_search);
        $string = strtolower($o_string);

        switch ($type) {
            case 'is':
                return $search == $string ? true : false;

                break;
            case 'is_not':
                return $search != $string ? true : false;

                break;
            case 'begins_with':
                return $search == substr($string, 0, strlen($search)) ? true : false;

                break;
            case 'ends_with':
                $len = strlen($search);

                return $search == substr($string, -$len, $len) ? true : false;

                break;
            case 'contains':
                return strpos($string, $search) === false ? false : true; //note opposite order of others
                break;
            case 'not_contain':
                return strpos($string, $search) === false ? true : false;

                break;
            case 'matches':
                return preg_match($o_search, $o_string);

                break;
        }
    }
}
