<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

use HS\Mail\SendFrom;
use HS\Mail\Attachments;
use HS\Jobs\SendMessage;
use HS\Mail\HelpspotAttachment;
use HS\Domain\Workspace\Document;
use HS\Mail\Mailer\MessageBuilder;

/**
Handles the assignment of notifications
Use: new
$notifier = new hs_notify(3456,232,__FILE__,__LINE__);
$notifier->SetRequestType('new');
$notifier->Notify();

use: existing
$notifier = new hs_notify(3456,232,__FILE__,__LINE__);
$notifier->SetRequestType('existing');
$notifier->Notify();
 */
class hs_notify
{
    // xPerson ID's of people to notify
    public $NotifyQueue = [];

    // Is this a new request or existing
    public $RequestType = 'new';

    // Existing request id
    public $RequestID = 0;

    // Person creationg notification
    public $NotificationCreator = 0;

    // Request history to notify about
    public $RequestHistoryID = 0;

    // If there is an assigned user put id here
    public $RequestAssignedUser = 0;

    // documents
    public $documents;

    // all staff
    public $allStaff;

    // all categories
    public $catlist;

    // File/Line
    public $file;

    public $line;

    // String representation of request changes
    // from HS_Request_Events.
    // Stored here in memory as they are not saved to database yet, but we
    // need them to add them to email notifications about request changes
    // See api.requests.lib.php::apiAddEditRequest() for usage (most likely the code path used)
    // See api.requests.lib.php::apiAddRequestHistory() for usage
    public $logEvents = '';

    /*****************************************
    CONSTRUCTOR AND MAIN METHODS
    *****************************************/

    // Constructor
    public function __construct($reqID, $reqHist, $creator, $f, $l)
    {
        $reqID = is_numeric($reqID) ? $reqID : 0;
        $reqHist = is_numeric($reqHist) || is_array($reqHist) ? $reqHist : 0;
        $creator = is_numeric($creator) ? $creator : 0;

        $this->RequestID = $reqID;
        $this->RequestHistoryID = $reqHist;
        $this->NotificationCreator = $creator;
        $this->file = $f;
        $this->line = $l;

        $this->allStaff = apiGetAllUsersComplete();
        $this->catlist = [];
        $cats = apiGetAllCategoriesComplete();
        while ($cat = $cats->FetchRow()) {
            $this->catlist[$cat['xCategory']] = $cat['sCategory'];
        }
        $this->catlist[0] = lg_inbox;

        $this->documents = new Attachments;
    }

    /**
     * Allow adding of HS_Request_Event sDescription items
     * Called by api.requests.lib.php::apiAddRequestHistory().
     * @param string $logEvents
     */
    public function setLogEvents($logEvents)
    {
        $this->logEvents = $logEvents;
    }

    // Main function
    public function Notify()
    {

        // RequestID must be set
        if (! empty($this->RequestID) && $this->RequestID != 0) {
            // notify assigned user if there is one. This may return 0 if there is not
            // Also checks to confirm that current user isn't assigned user
            $this->_GetAssignedUser($this->RequestID);

            switch ($this->RequestType) {
                case 'new':
                    // If user is not assigned then check for others to notify
                    if ($this->RequestAssignedUser == 0) {
                        // add each person on the new request notify list to queue
                        $this->_GetNewRequestNotifyList();
                    }

                break;
                case 'existing':
                case 'mailrule':
                case 'automation':
                    $this->_GetSubscribers($this->RequestID);

                break;
            }

            if(! empty($this->RequestHistoryID) && $this->RequestHistoryID != 0) {
                $this->_GetHistoryDocuments($this->RequestHistoryID);
            }

            // Make sure only one update is sent per person.
            // When we move to Laravel 5.x we can substitute for:
            // $this->NotifyQueue = collect($this->NotifyQueue)->unique('person')->toArray();
            $temp = [];
            $this->NotifyQueue = array_filter($this->NotifyQueue, function ($v) use (&$temp) {
                if (in_array($v['person'], $temp)) {
                    return false;
                } else {
                    array_push($temp, $v['person']);

                    return true;
                }
            });

            // send notifications
            foreach ($this->NotifyQueue as $person) {
                $this->_DoNotifications($person);
            }
        }
    }

    /*****************************************
    PUBLIC METHODS
    *****************************************/

    // Set request type
    public function SetRequestType($type)
    {
        if ($type == 'new') {
            $this->RequestType = 'new';
        } elseif ($type == 'existing') {
            $this->RequestType = 'existing';
        } elseif ($type == 'mailrule') {
            $this->RequestType = 'mailrule';
        } elseif ($type == 'automation') {
            $this->RequestType = 'automation';
        }
    }

    // This method is used to add an arbitrary user to the notification. One who is not assigned and not subscribed.
    // 		currently this is used to assign the former user of a reassigned request so that they are notified of the reassignment
    public function AddToNotifyQueue($xpersonid, $ccstaff = false)
    {
        $this->_AddToNotifyQueue($xpersonid, $ccstaff);
    }

    /*****************************************
    PRIVATE METHODS
    *****************************************/

    // Set initial people to assign - can call muliple times to add multiple people
    public function _AddToNotifyQueue($xpersonid, $ccstaff)
    {
        if ($xpersonid != 0) {
            $this->NotifyQueue[] = ['person' => $xpersonid, 'ccstaff' => $ccstaff];
        }
    }

    // Add people who are subscribed already to this request to the queue
    public function _GetSubscribers($reqID)
    {
        $checkSubs = apiGetActiveRequestSubscribers($reqID);

        if ($checkSubs) {
            while ($sub = $checkSubs->FetchRow()) {
                if ($sub['xPerson'] != $this->NotificationCreator) {
                    $this->_AddToNotifyQueue($sub['xPerson'], true);
                }
            }

            return true;
        }
    }

    // Get list of users who should be notified when new requests come in
    public function _GetNewRequestNotifyList()
    {
        $limited = [];
        $request = apiGetRequest($this->RequestID);
        $staff = apiGetAllUsers();
        foreach ($staff as $person) {
            if ($person['fLimitedToAssignedCats'] == 1) {
                $limited[] = $person['xPerson'];
            }
        }

        // get users who want notification on new requests
        $checkNew = $GLOBALS['DB']->Execute('SELECT xPerson FROM HS_Person WHERE fNotifyNewRequest = 1 AND fDeleted = 0');

        while ($sub = $checkNew->FetchRow()) {
            // If they are limited and not assigned to the category then do not notify them.
            if (in_array($sub['xPerson'], $limited) and ($request['xCategory'] == 0 or ! in_array($request['xCategory'], apiGetUserCats($sub['xPerson'])))) {
                continue;
            }
            $this->_AddToNotifyQueue($sub['xPerson'], false);
        }

        return true;
    }

    // Get assigned user for this request
    public function _GetAssignedUser($reqID)
    {
        $checkUser = $GLOBALS['DB']->GetRow('SELECT xPersonAssignedTo FROM HS_Request WHERE xRequest = ?', [$reqID]);
        $this->RequestAssignedUser = $checkUser['xPersonAssignedTo'];

        // if user initializing notification isn't assigned user then send notification to assigned user
        if ($this->NotificationCreator != $this->RequestAssignedUser) {
            $this->_AddToNotifyQueue($this->RequestAssignedUser, false);
        }

        return true;
    }

    public function _DoNotifications($notifyPerson)
    {
        global $user;

        $xperson = $notifyPerson['person'];

        if ($xperson != 0) {
            if ($this->RequestType == 'new') {
                $reqtype = lg_mailsub_new;
                $inmaillabel = lg_feed_request;
            } elseif ($this->RequestType == 'mailrule') {
                $reqtype = lg_mailsub_mailrule;
                $inmaillabel = lg_feed_notification;
            } elseif ($this->RequestType == 'automation') {
                $reqtype = lg_mailsub_automation;
                $inmaillabel = lg_feed_notification;
            } else {
                $reqtype = lg_mailsub_existing;
                $inmaillabel = lg_feed_update;
            }

            //Get person details
            $person = apiGetUser($xperson);

            // If the person is deleted do not notify them.
            if ($person['fDeleted'] == 1) {
                return;
            }

            //Get request/history
            $req = apiGetRequest($this->RequestID);

            /******** SEND EMAIL ************/

            if (($notifyPerson['ccstaff'] || $person['fNotifyEmail'] || $person['fNotifyEmail2']) && $this->RequestHistoryID != 0) {
                //Handle request updates, which send 2 req hists in one email
                if (is_array($this->RequestHistoryID)) {
                    $hisbody = apiGetHistoryEvent($this->RequestHistoryID[0]); //his body
                    $hislog = apiGetHistoryEvent($this->RequestHistoryID[1]); //his log
                    if (hs_setting('cHD_HTMLEMAILS')) {
                        $body = (! $hisbody['fNoteIsHTML'] ? nl2br($hisbody['tNote']) : $hisbody['tNote']).'<br /><hr width="80%"><br />'.nl2br($hislog['tLog']).'<br />';
                        $body .= nl2br($this->logEvents).'<br />';
                    } else {
                        $body = $hisbody['tNote']."\n\n-------\n".$hislog['tLog'];
                        $body .= "\n".$this->logEvents;
                    }
                } else {
                    $history = apiGetHistoryEvent($this->RequestHistoryID);
                    //also check to make sure it's not a ccstaff array
                    if (! hs_empty($history['tLog']) && utf8_strpos($history['tLog'], 'ccstaff') === false) {
                        $body = trim($history['tLog']);
                        $body .= "\n".$this->logEvents;
                    } else {
                        $body = trim($history['tNote']);
                    }
                    //Format
                    if (hs_setting('cHD_HTMLEMAILS') && $history['fInitial'] == 1 && $req['fOpenedVia'] == 7) { //handle portal notes
                        $body = makeBold(nl2br(hs_htmlspecialchars($body)));
                    } elseif (hs_setting('cHD_HTMLEMAILS') && ! $history['fNoteIsHTML']) { //handle non-html notes and logs
                        $body = nl2br(hs_htmlspecialchars($body));
                    } elseif (hs_setting('cHD_HTMLEMAILS') == 0 && $history['fNoteIsHTML']) { //handle HTML note, but system is in text mode
                        $body = strip_tags(hs_html_entity_decode($body));
                    }
                }

                // Set inline images with correct CID
                $body = $this->_MakeValidCIDs($body);

                //Build email
                $tos  = [];
                if ($person['fNotifyEmail'] || $notifyPerson['ccstaff']) {
                    $tos[] = $person['sEmail'];
                }
                if ($person['fNotifyEmail2'] && ! empty($person['sEmail2'])) {
                    $tos[] = $person['sEmail2'];
                }

                //Build message body
                $vars = getPlaceholders([
                    'email_subject' => $reqtype,
                    'tracking_id' => '{'.trim(hs_setting('cHD_EMAILPREFIX')).$this->RequestID.'}',
                    'label' => $inmaillabel,
                    'subject' => $req['sTitle'],
                    'name' => $user['sFname'].' '.$user['sLname'],
                    'requestdetails' => renderRequestTextHeader($req, $this->allStaff, $this->catlist),
                    'requestdetails_html' => renderRequestTextHeader($req, $this->allStaff, $this->catlist, 'html'),
                    'requestcheckurl' => cHOST.'/admin?pg=request&reqid='.$req['xRequest'],
                ], $req);

                $message = (new MessageBuilder(SendFrom::default(), $this->RequestID))
                    ->to($tos)
                    ->subject('staff', $vars);

                if ($notifyPerson['ccstaff']) {
                    $message->body('ccstaff', $body, $vars);
                } else {
                    $message->body('staff', $body, $vars);
                }

                SendMessage::dispatch($message, $this->documents)
                    ->onQueue(config('queue.high_priority_queue')); // mail.private
            }

            /******** SEND SMS MAIL ************/
            if (! hs_empty($person['sSMS']) && ($person['fNotifySMS'] || ($person['fNotifySMSUrgent'] && $req['fUrgent'])) && $this->RequestHistoryID != 0) { //send sms if set to send all or if set to send urgent only
                //Handle request updates, which send 2 req hists in one email
                if (is_array($this->RequestHistoryID)) {
                    $history = apiGetHistoryEvent($this->RequestHistoryID[0]);
                    $hislog = apiGetHistoryEvent($this->RequestHistoryID[1]);
                    $note = $history['tNote']."\n\n".$hislog['tLog'];
                    $note .= "\n".$this->logEvents;
                } else {
                    $history = apiGetHistoryEvent($this->RequestHistoryID);
                    $note = $history['tNote'];
                }

                $sms = apiGetSMS($person['xSMSService']);

                $sms_msg = strip_tags($note);
                if (! hs_empty($sms_msg)) {
                    //Prob here where it could send serialized php
                    //$sms_msg	.= !hs_empty($history['tLog']) ? "\n\n".lg_feed_log.": \n".$history['tLog'] : '';

                    $vars = getPlaceholders(['label' => $inmaillabel], $req);
                    $message = (new MessageBuilder(SendFrom::default(), $this->RequestID))
                        ->to($person['sSMS'].'@'.$sms['sAddress'])
                        ->body('sms', $sms_msg, $vars);

                    // For SMS we need to manually truncate message after it's passed through hs_body
                    $message->setBodyHtml(utf8_substr($message->getHtml(), 0, ($sms['sMsgSize'] - 10))) ;
                    $message->setBodyText(utf8_substr($message->getText(), 0, ($sms['sMsgSize'] - 10))) ;

                    // Note: Empty attachments object for SMS
                    SendMessage::dispatch($message)
                        ->onQueue(config('queue.high_priority_queue')); // mail.private
                }
            }
        }//End person not 0 check
    }

    public function _GetHistoryDocuments($requestHistoryId)
    {
        $historyIds = (is_array($requestHistoryId)) ? $requestHistoryId : [$requestHistoryId];

        return Document::noBlob()
            ->whereIn('xRequestHistory', $historyIds)
            ->get()
            ->each(function(Document $document) {
                $isInline = ($document->sCID) ? true : false;
                $this->documents->push(new HelpspotAttachment($document->xDocumentId, $isInline));
            });
    }

    public function _MakeValidCIDs($content)
    {
        $inlines = getInlineCIDs($content);

        if (! empty($inlines[1])) {
            foreach ($inlines[1] as $cid) {
                if(strpos($cid, '@') === false) {
                    $content = str_replace($cid, $cid . '@' . $this->_GetHostname(), $content);
                }
            }
        }

        return $content;
    }

    public function _GetHostname()
    {
        if( defined('cHOST') ) {
            return parse_url(cHOST, PHP_URL_HOST);
        }

        return 'helpspot.com';
    }
}
