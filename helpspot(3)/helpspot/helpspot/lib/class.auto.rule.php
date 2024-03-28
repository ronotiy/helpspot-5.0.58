<?php

use HS\Mail\SendFrom;
use HS\Jobs\SendMessage;
use HS\Mail\Mailer\MessageBuilder;
use Illuminate\Support\Str;

/**
Represents one email message
*/
class hs_auto_rule
{
    public $id = null;

    public $name = '';

    //name of rule
    public $anyall = 'all';

    //match any or all
    public $option_no_notifications = '0';

    //suppress notifications
    public $option_direct_call_only = '0';

    //only run once
    public $option_once = '0'; // let old existing AR's run max

    //only run rule when called directly by tasks2.php
    public $displayColumns = ['view', 'fOpenedVia', 'fOpen', 'fullname', 'reqsummary', 'age'];

    public $displayColumnsWidths = [];

    //Override widths
    public $filter_urgent_inline = 0;

    //Show urgent requests inline
    public $filter_orderby = 'xRequest';

    //Default order column
    public $filter_orderbydir = 'DESC';

    //Default order direction
    public $filter_groupby = '';

    //Default grouping column
    public $filter_groupbydir = 'ASC';

    //Default grouping direction
    public $filter_folder = '';

    //Folder filter is in
    public $filter_shortcut = '';

    //Keyboard shortcut
    public $CONDITIONS = [];

    //Array of conditions. Format array([IF]=>"To",[IS]=>"is not",[VALUE]=>"pizza")
    public $ACTIONS = [];

    //Action list. Format array([ACTION]=>"optional") ex: array('assign to'=>3)
    public $returnrs = false;	//return the result set instead of performing actions

    /**
     * Constructor.
     */
    public function __construct()
    {
        //Call to get rules
        //Init rules
    }

    /**
     * Take in the POST array and parse out automation rule.
     */
    public function SetAutoRule(&$posts)
    {
        if (isset($posts['sRuleName'])) {
            $this->name = $posts['sRuleName'];
        }
        if (isset($posts['sFilterName'])) {
            $this->name = $posts['sFilterName'];
        } //when used in filters
        if (isset($posts['anyall'])) {
            $this->anyall = $posts['anyall'];
        }
        if (isset($posts['option_no_notifications'])) {
            $this->option_no_notifications = $posts['option_no_notifications'];
        }
        if (isset($posts['option_direct_call_only'])) {
            $this->option_direct_call_only = $posts['option_direct_call_only'];
        }
        if (isset($posts['displayColumns'])) {
            $this->displayColumns = $posts['displayColumns'];
        }
        if (isset($posts['urgentinline'])) {
            $this->filter_urgent_inline = $posts['urgentinline'];
        }
        if (isset($posts['orderBy'])) {
            $this->filter_orderby = $posts['orderBy'];
        }
        if (isset($posts['orderByDir'])) {
            $this->filter_orderbydir = $posts['orderByDir'];
        }
        if (isset($posts['groupBy'])) {
            $this->filter_groupby = $posts['groupBy'];
        }
        if (isset($posts['groupByDir'])) {
            $this->filter_groupbydir = $posts['groupByDir'];
        }
        if (isset($posts['sFilterFolder'])) {
            $this->filter_folder = $posts['sFilterFolder'];
        }
        if (isset($posts['sShortcut'])) {
            $this->filter_shortcut = $posts['sShortcut'];
        }
        $this->option_once = (isset($posts['option_once']) ? 1 : 0);

        foreach ($posts as $k=>$v) {
            //Pass in first values, the other method will pull rest out of posts array
            if (strpos($k, 'condition') !== false && strpos($k, '_1') && ! empty($posts[$k])) {

                //Sub group vs normal condition logic
                $subfield = str_replace('_1', '_subgroup', $k);
                if (isset($posts[$subfield])) {
                    //If this field is part of a sub group we put it in an array of the other subgroup conditions
                    $this->CONDITIONS[$posts[$subfield]][] = $this->setCondition($k, $v, $posts);
                } else {
                    //Add the condition
                    $this->CONDITIONS[] = $this->setCondition($k, $v, $posts);
                }
            }

            //Set actions
            if (strpos($k, 'action') !== false && strpos($k, '_1') && ! empty($posts[$k])) {
                $this->ACTIONS[] = $this->setAction($k, $v, $posts);
            }

            //Build display column widths
            if (strpos($k, 'column_width_') !== false) {
                $field = str_replace('column_width_', '', $k);
                $field = str_replace('_value', '', $field);
                $this->displayColumnsWidths[$field] = $v;
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
            case 'sUserId':
            case 'sEmail':
            case 'sFirstName':
            case 'sLastName':
            case 'sPhone':
            case 'sTitle':
            case 'dtSinceCreated':
            case 'dtSinceClosed':
            case 'lastupdate':
            case 'lastpubupdate':
            case 'lastcustupdate':
            case 'ctPublicUpdates':
            case 'speedtofirstresponse':
            case 'xRequest':
            case 'xOpenedViaId':
            case 'fOpenedVia':
            case 'xPortal':
            case 'xStatus':
            case 'xPersonAssignedTo':
            case 'xPersonOpenedBy':
            case 'xCategory':
            case 'acFromTo':
            case 'thermostat_nps_score':
            case 'thermostat_csat_score':
                return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>trim($posts[$_3rdfield])];

                break;
            case 'thermostat_feedback':
            case 'fOpen':
            case 'reportingTags':
            case 'sSearch':
            case 'wheresql':
            case 'iLastReplyBy':
            case 'relativedate':
            case 'relativedateclosed':
            case 'relativedatetoday':
            case 'relativedatelastpub':
            case 'relativedatelastcust':
            case 'updatedby':
            case 'subconditions_and':
            case 'subconditions_or':
            case 'acWasEver':
            case 'acReassignedBy':
            case 'betweenDates': //only used by reports
            case 'betweenClosedDates': //only used by reports
            case 'betweenTTDates': //only used by reports
                return ['IF'=>trim($value), 'VALUE'=>trim($posts[$_2ndfield])];

                break;

            case 'beforeDate':
            case 'afterDate':
            case 'closedBeforeDate':
            case 'closedAfterDate':
                return ['IF'=>trim($value), 'VALUE'=>trim(jsDateToTime($posts[$_2ndfield], hs_setting('cHD_POPUPCALSHORTDATEFORMAT')))];

                break;

            case 'fUrgent':
            case 'fNotUrgent':
                return ['IF'=>trim($value)];

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
                            return ['IF'=>trim($value), 'VALUE'=>trim($posts[$_2ndfield])];

                            break;
                        case 'numtext':
                        case 'text':
                        case 'drilldown':
                        case 'regex':
                        case 'ajax':
                        case 'decimal':
                            return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>trim($posts[$_3rdfield])];

                            break;
                        case 'date':
                            $fieldValue = jsDateToTime(trim($posts[$_3rdfield]), hs_setting('cHD_POPUPCALSHORTDATEFORMAT'));
                            return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>$fieldValue];
                            break;
                        case 'datetime':
                            $fieldValue = jsDateToTime(trim($posts[$_3rdfield]), hs_setting('cHD_POPUPCALDATEFORMAT'));
                            return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>$fieldValue];
                            break;
                        //Special for selects, handles old format IF/Value vs new format of IF/IS/VALUE
                        case 'select':

                            if (is_null($posts[$_3rdfield])) {
                                $posts[$_3rdfield] = '';
                            }

                            if (isset($posts[$_3rdfield])) {
                                return ['IF'=>trim($value), 'IS'=>trim($posts[$_2ndfield]), 'VALUE'=>trim($posts[$_3rdfield])];
                            } else {
                                return ['IF'=>trim($value), 'VALUE'=>trim($posts[$_2ndfield])];
                            }

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
                        $fields['setcustomfield'][$custid] = $posts[$custid];
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
            case 'webhook':
			case 'thermostat_send':
			case 'subscribe_staff':
			case 'unsubscribe_staff':
			case 'thermostat_add_email':
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

    /**
     * Parses conditions and returns a valid filter array.
     */
    public function getFilterConditions()
    {
        $filterDef = [];

        //Setup filter conditions
        $filterDef['sFilterName'] = $this->name;
        $filterDef['displayColumns'] = $this->displayColumns;
        $filterDef['displayColumnsWidths'] = $this->displayColumnsWidths;
        $filterDef['anyall'] = $this->anyall;
        $filterDef['urgentinline'] = $this->filter_urgent_inline;
        $filterDef['orderBy'] = $this->filter_orderby;
        $filterDef['orderByDir'] = $this->filter_orderbydir;
        $filterDef['groupBy'] = $this->filter_groupby;
        $filterDef['groupByDir'] = $this->filter_groupbydir;
        $filterDef['sFilterFolder'] = $this->filter_folder;
        $filterDef['sShortcut'] = $this->filter_shortcut;

        //Conditional checks
        foreach ($this->CONDITIONS as $k=>$v) {

            //If it's a sub group handle that, else it's just a single condition
            if (! is_numeric($k)) {
                foreach ($v as $subk=>$subv) {
                    $filterDef[$k][$subv['IF']][] = $this->_translateCondition($subv);
                }
            } else {
                $filterDef[$v['IF']][] = $this->_translateCondition($v);
            }
        }//end foreach

        return $filterDef;
    }

    public function _translateCondition($v)
    {
        switch ($v['IF']) {
            case 'sUserId':
            case 'sEmail':
            case 'sFirstName':
            case 'sLastName':
            case 'sPhone':
            case 'sTitle':
            case 'dtSinceCreated':
            case 'dtSinceClosed':
            case 'lastupdate':
            case 'lastpubupdate':
            case 'lastcustupdate':
            case 'ctPublicUpdates':
            case 'speedtofirstresponse':
            case 'xRequest':
            case 'xOpenedViaId':
            case 'fOpenedVia':
            case 'xStatus':
            case 'xPortal':
            case 'xPersonAssignedTo':
            case 'xPersonOpenedBy':
            case 'xCategory':
            case 'acFromTo':
            case 'thermostat_nps_score':
            case 'thermostat_csat_score':
                //Stored as an multiarray. this allows multiple criteria of the same type to be passed in.
                return ['op'=>$v['IS'], 'value'=>$v['VALUE']];

                break;
            case 'thermostat_feedback':
            case 'fOpenedVia':
            case 'fOpen':
            case 'reportingTags':
            case 'sSearch':
            case 'wheresql':
            case 'iLastReplyBy':
            case 'relativedate':
            case 'relativedateclosed':
            case 'relativedatetoday':
            case 'relativedatelastpub':
            case 'relativedatelastcust':
            case 'beforeDate':
            case 'afterDate':
            case 'closedBeforeDate':
            case 'closedAfterDate':
            case 'updatedby':
            case 'subconditions_and':
            case 'subconditions_or':
            case 'acWasEver':
            case 'acReassignedBy':
            case 'betweenDates': //only used by reports
            case 'betweenClosedDates': //only used by reports
            case 'betweenTTDates': //only used by reports
                //Stored as an multiarray. this allows multiple criteria of the same type to be passed in.
                return $v['VALUE'];

                break;

            case 'fUrgent':
                return 1;

                break;
            case 'fNotUrgent':
                return 0;

                break;
        }

        //Custom fields
        if (isset($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                $fid = 'Custom'.$fvalue['fieldID'];
                if ($v['IF'] == $fid) {
                    switch ($fvalue['fieldType']) {
                        case 'checkbox':
                        case 'lrgtext':
                            return $v['VALUE'];

                            break;
                        case 'numtext':
                        case 'text':
                        case 'drilldown':
                        case 'date':
                        case 'datetime':
                        case 'regex':
                        case 'ajax':
                        case 'decimal':
                            return ['op'=>$v['IS'], 'value'=>$v['VALUE']];

                            break;
                        //Special for selects, handles old format IF/Value vs new format of IF/IS/VALUE
                        case 'select':
                            if (isset($v['IS'])) {
                                return ['op'=>$v['IS'], 'value'=>$v['VALUE']];
                            } else {
                                return $v['VALUE'];
                            }

                            break;
                    }
                }
            }
        }
    }

    /**
     * Apply rules to the pased in message object. Should be of type defined in class.imap.message.php
     * $msg is an imap.message object
     * $mailbox is an array of mailbox info.
     * @param bool $debug
     * @return \HS\Database\RecordSet
     */
    public function ApplyRule($debug = false)
    {
        if ($debug) {
            echo ">>>> Rule: {$this->name}\n";
        }

        //RUN FILTER
        $ft = new hs_filter();
        $ft->filterDef = $this->getFilterConditions();

        $rs = $ft->outputResultSet();

        //Return the result instead of doing actions
        if ($this->returnrs) {
            return $rs;
        }

        //PERFORM ACTIONS
        if (hs_rscheck($rs)) {
            while ($row = $rs->FetchRow()) {
                //Setup new request
                $req = $row;

                // is this over the limit?
                $totalRuns = (int) autoRuleTotalRuns($row['xRequest'], $this->id);

                // Should we bail out? Two conditions, first for a single run and second for max
                if ($this->option_once && $totalRuns >= 1) {
                    if ($debug) echo ">>>> Rule has already ran on request #" . $req['xRequest'] . " \n";
                    continue;
                }
                if ($totalRuns >= hs_setting('cHD_MAX_AUTO_RUNS', 50)) {
                    if ($debug) echo ">>>> Rule has already ran the max times on request #" . $req['xRequest'] . " \n";
                    continue;
                }

                incrementRuleRuns($row['xRequest'], $this->id);

                //Log notes
                $lognotes = '';

                $req['dtGMTOpened'] = time();	//Open field is used by requestcheck to set change time.

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
                                if ($v == 2) {
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
                                    $email = trim($v['staffmember']);
                                    // Is this a custom field they want to use?
                                    if (Str::startsWith($email, '##CUSTOM')) {
                                        $field = str_replace('##', '', $email);
                                        $field = ucfirst(strtolower($field)); // Convert "CUSTOM" to "Custom" so it matches the $req key.
                                        $email = (isset($req[$field])) ? $req[$field] : $email;
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

                                // Verify the email we has is valid before trying
                                // to send it. see https://github.com/UserScape/HelpSpot/issues/816
                                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    $do_notify = false;
                                    errorLog('* Request:'.$req['xRequest'].': '.$email.' is not a valid email.', 'Mail Rule '.$this->name.' failed', __FILE__, __LINE__);
                                }

                                if (! empty($email) && $do_notify) {
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
                                    // $v['email'] = tokenReplace($v['email'], getPlaceholders([], $req)); # TODO: I don't believe we need this any longer
                                    if ($k == 'auto_notifysms') {
                                        $v['email'] = utf8_substr($v['email'], 0, ($sms['sMsgSize'] - 10));
                                    } else {
                                        $v['original_markdown_text'] = $v['email'];
                                        $v['email'] = hs_markdown($v['email']);
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

                                    $messageBuilder = (new MessageBuilder($sendFrom, $req['xRequest']))
                                        ->to($tos)
                                        ->setType('public')
                                        ->subject($email_template, $vars)
                                        ->body($email_template, $v['email'], $vars);

                                    SendMessage::dispatch($messageBuilder, $attachments=null, $publicEmail=true)
                                        ->onQueue(config('queue.high_priority_queue')); // mail.public

                                    if ($k == 'auto_notify') {
                                        //Log notifiction email
                                        $lognotes .= "\n".lg_notified.": {$user['sFname']} {$user['sLname']}";
                                    } elseif ($k == 'auto_notifysms') {
                                        //Log notifiction email
                                        $lognotes .= "\n".lg_notifiedsms.": {$user['sFname']} {$user['sLname']}";
                                    } elseif ($k == 'auto_notifyexternal') {
                                        $notified = $v['staffmember'];
                                        if (strpos($v['staffmember'], '##CUSTOM') !== false) {
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
                                foreach ($ll_sources as $k => $source) {
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
                                        foreach ($GLOBALS['customFields'] as $k => $v) {
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
                                $req['override_autoassign'] = true; //override auto assign so OOO doesn't cause an unintended reassignment when just sending email table
                                break;

                            case 'subscribe_staff':
                                apiSubscribeToRequest($req['xRequest'], $v);

                                break;

                            case 'unsubscribe_staff':
                                apiUnsubscribeToRequest($req['xRequest'], $v);

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

                            case "thermostat_send":
								include_once(cBASEPATH . '/helpspot/lib/api.thermostat.lib.php');
								sendThermostatSurvey($req, $v); // where is survey id set? $v['xSurvey']
								break;

							case "thermostat_add_email":
								include_once(cBASEPATH . '/helpspot/lib/api.thermostat.lib.php');
								addThermostatEmail($req, $v); // where is survey id set?
								break;

                            case 'webhook':

                                //Add in request history
                                $req['request_history'] = ['item'=>hs_clean_req_history_for_API($req['xRequest'], $req, 0)];

                                hsPost($v, $req);

                                break;
                        }
                    }
                }

                // Should we skip all updates?
                $skipUpdate = false;
                if (count(array_unique($this->ACTIONS)) === 1 and isset($this->ACTIONS[0]['auto_emailresults'])) {
                    // there is only on action type and the type is email table of results
                    $skipUpdate = true;
                }

                //Save action changes
                if (! $skipUpdate) {
                    $update = new requestUpdate($req['xRequest'], $req, -1, __FILE__, __LINE__);
                    $update->log_heading = lg_automation.': '.$this->name;
                    if (! empty($lognotes)) {
                        $update->logNote = $lognotes;
                    } //add log note
                    $update->notify = ($this->option_no_notifications ? 0 : 1); //Do or don't do notifications
                    $reqResult = $update->checkChanges();
                }
            }
        }

        //Sending email reports of result set
        $email_reports_to = [];

        foreach ($this->ACTIONS as $k=>$v) {
            if (isset($v['auto_emailresults'])) {
                $email_reports_to[] = $v['auto_emailresults'];
            }
        }

        if (! empty($email_reports_to) && $rs->RecordCount() > 0) {
            $allStaff = apiGetAssignStaff();

            //Create table of results
            $rs->Move(0);

            $body = '<table width="100%">';
            $body .= '<tr><th colspan="7" style="font-size:16px;">'.hs_htmlspecialchars($this->name).'</th></tr>
							<tr>
								<th></th>
								<th align="left">'.lg_lookup_filter_open.'</th>
								<th align="left">'.lg_lookup_filter_custname.'</th>
								<th align="left">'.lg_lookup_filter_assignedto.'</th>
								<th align="left">'.lg_lookup_filter_status.'</th>
								<th align="left">'.lg_lookup_filter_category.'</th>
								<th align="left">'.lg_lookup_filter_timeopen.'</th>
							</tr>';
            while ($row = $rs->FetchRow()) {
                $body .= '<tr style="background-color:#e6e6e6">
									<td align="center"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]).'">'.$row['xRequest'].'</a></td>
									<td>'.($row['fOpen'] ? lg_yes : lg_no).'</td>
									<td>'.hs_htmlspecialchars($row['sFirstName']).' '.hs_htmlspecialchars($row['sLastName']).'</td>
									<td>'.($row['xPersonAssignedTo'] ? hs_htmlspecialchars($allStaff[$row['xPersonAssignedTo']]['namereq']) : '-').'</td>
									<td>'.hs_htmlspecialchars($row['sStatus']).'</td>
									<td>'.($row['sCategory'] ? hs_htmlspecialchars($row['sCategory']) : '-').'</td>
									<td>'.hs_showDate($row['dtGMTOpened']).'</td>
								</tr>';
            }
            $body .= '</table>';

            $tos = [];
            foreach ($email_reports_to as $uid) {
                $user = apiGetUser($uid);
                if (! $user || userIsDeleted($user)) { // if the user is deleted bail out
                    break;
                }
                $tos[] = $user['sEmail'];
                if ($user['sEmail2'] && $user['fNotifyEmail2']) {
                    $tos[] = $email2;
                }
            }

            $messageBuilder = (new MessageBuilder(SendFrom::default()))
                ->to($tos)
                ->setSubject($this->name)
                ->setBodyHtml($body);

            SendMessage::dispatch($messageBuilder)
                ->onQueue(config('queue.high_priority_queue')); // mail.private
        }
    }

    /**
     * Used to convert pre 1.5.0 filters to new filter scheme.
     */
    public function ConvertFromOldFilterScheme($old_def, $name)
    {
        $this->name = $name;
        $this->displayColumns = $old_def['displayColumns'];
        $this->anyall = 'all';
        $this->filter_urgent_inline = $old_def['urgentinline'];
        $this->filter_orderby = $old_def['orderBy'];
        $this->filter_orderbydir = $old_def['orderByDir'];
        $this->filter_folder = $old_def['sFilterFolder'];
        $this->filter_shortcut = $old_def['sShortcut'];

        foreach ($old_def as $k=>$v) {
            if ($k == 'xCategory' || $k == 'xStatus' || $k == 'xPersonAssignedTo' || $k == 'xPersonOpenedBy' || $k == 'xOpenedViaId' || $k == 'fOpenedVia') {
                $default_is = 'is';
            } elseif ($k == 'dtSinceCreated' || $k == 'dtSinceClosed') {
                $default_is = 'greater_than';
            } else {
                $default_is = 'contains';
            }

            if (! hs_empty($v) && ! hs_empty($k) && $k != 'sFilterName' && $k != 'displayColumns' && $k != 'urgentinline' && $k != 'orderBy'
                && $k != 'orderByDir' && $k != 'fShowCount' && $k != 'fCustomerFriendlyRSS' && $k != 'sFilterFolder' && $k != 'sShortcut') {

                //Mailbox variable has changed
                $k = ($k == 'xMailbox') ? 'xOpenedViaId' : $k;
                $k = ($k == 'sEmailSub') ? 'sTitle' : $k;

                //Store filter info
                if ($k == 'reportingTags') {
                    foreach ($v as $key=>$tag) {
                        $this->CONDITIONS[] = ['IF'=>trim($k), 'IS'=>$default_is, 'VALUE'=>$tag];
                    }
                } elseif ($k == 'openForHours') {
                    $this->CONDITIONS[] = ['IF'=>'dtSinceCreated', 'IS'=>'greater_than', 'VALUE'=>($v * 60)];
                } elseif ($k == 'openForLessHours') {
                    $this->CONDITIONS[] = ['IF'=>'dtSinceCreated', 'IS'=>'less_than', 'VALUE'=>($v * 60)];
                } elseif ($k == 'daysOld') {
                    $this->CONDITIONS[] = ['IF'=>'dtSinceCreated', 'IS'=>'greater_than', 'VALUE'=>($v * 24 * 60)];
                } else {
                    if ($k == 'afterDate' || $k == 'beforeDate') {
                        $v = hs_showCustomDate($v, hs_setting('cHD_POPUPCALSHORTDATEFORMAT'));
                    }
                    $this->CONDITIONS[] = ['IF'=>trim($k), 'IS'=>$default_is, 'VALUE'=>trim(str_replace('*', '', $v))];
                }
            }
        }
    }
}
