<?php

namespace HS\IncomingMail\Mailman;

use hs_notify;
use requestUpdate;
use HS\IncomingMail\Message;
use HS\Domain\Workspace\Request;
use Illuminate\Support\Facades\Log;
use HS\IncomingMail\Processors\LoopCheck;
use HS\IncomingMail\Processors\StaffReply;

class UpdateRequest
{
    private $logger;
    private $mailbox;
    private $mailboxId;
    private $loopCheck;

    public function __construct(array $mailbox, $logger)
    {
        $this->logger = $logger;
        $this->mailbox = $mailbox;
        $this->mailboxId = $mailbox['xMailbox'];
        $this->loopCheck = new LoopCheck;
    }

    public function save(Message $message, $msgRequestId, $origReq, $msgFiles)
    {
        $msgFromEmail = $message->getFromEmail();
        $msgDate = date('U');

        if ($this->loopCheck->updateInLoop($message, $msgRequestId, $msgDate)) {
            $this->logger->display('Message flagged as a loop. Loop broken, message not stored');
            Log::debug('['.get_class($this).'] Message flagged as a loop. Loop broken, message not stored', [
                'From Email' => $msgFromEmail,
            ]);
            errorLog(sprintf(lg_autoreply_loop_break, $msgFromEmail), 'Email Importing', __FILE__, __LINE__);
            return false;
        }

        // See if email is from a system user and apply history appropriately
        $personId = null;
        $user = null;
        if (! empty($msgFromEmail)) {
            $user = apiGetUserByEmail($msgFromEmail);
            if ($user) {
                $personId = $user['xPerson'];
            }
        }

        // Is it an out of control update? Make sure the request history hasn't passed the max threshold.
        if (Request::reachedHistoryLimit($msgRequestId)) {
            $this->logger->display("The MAX request history for $msgRequestId has been reached, message not stored");
            errorLog(sprintf(lg_history_over_limit, $msgRequestId, $msgFromEmail), 'Email Importing', __FILE__, __LINE__);
            return false;
        }

        // First we check to see if this email is from the customer, if so it's public.
        $public = 0;
        if (strtolower($origReq['sEmail']) == strtolower($msgFromEmail)) {
            $public = 1;
            $personId = 0; // If it's staff having their own public request then remove the personid.
        } else {
            // is this a staff notification? <hs-notification header:
            $staffReply = new StaffReply($message, $msgRequestId, $user);
            if ($staffReply->isNotification()) {
                return $staffReply->save($origReq, $this->logger);
            }
        }

        //If request was in trash we should remove it
        if (intval($origReq['fTrash']) === 1) {
            // Reopen request
            $origReq['fTrash'] = 0;
            $origReq['dtGMTTrashed'] = 0;
            $origReq['dtGMTOpened'] = $msgDate;	//current dt

            $update = new requestUpdate($msgRequestId, $origReq, 0, __FILE__, __LINE__);
            $update->notify = false; //notify below instead
            $update->checkChanges();
        }

        // vars for saving:
        $msgMessage = $message->getBody();

        /*
         * TODO: Possible refactorings/notes:
         *      1. The below case of re-opening a closed request vs adding to currently open a request is very similar
         *      2. Reopening a closed request adds the the new customer note and the log created by the requestUpdate class to the note
         *      3. Adding to a request that's open does not add that, although it could easily
         *      4. A difference that must remain is that triggers are only run on requests that are currently open
         */

        // Initialize defaults for staff notifications
        $notificationCreator = 0;
        $notificationRequestIds = [];

        // If the request has been closed then set it to be reopened - give HD option to close again or create new request
        //	based on this message.
        if (intval($origReq['fOpen']) === 0) {
            // Reopen request
            $origReq['fOpen'] = 1;
            $origReq['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 1);
            $origReq['dtGMTOpened'] = $msgDate;	//current dt
            //if the user isn't active then send to inbox
            $user = apiGetUser($origReq['xPersonAssignedTo']);
            if ($user['fDeleted'] == 1) {
                $origReq['xPersonAssignedTo'] = 0;
            }

            // Add new note. This is done before the triggers so that fullpublichistory is accurate
            $reqHis = apiAddRequestHistory([
                'xPerson' => $personId,
                'xRequest' => $msgRequestId,
                'dtGMTChange' => $msgDate,
                'fPublic' => $public,
                'tNote' => $msgMessage,
                'sRequestHistoryHash' => md5(trim($message->getBody())),
                'fNoteIsHTML' => $message->isHtml(),
                'tEmailHeaders' => hs_serialize($message->headers),
            ]);

            // Do a request update check even if the request is open.
            // https://github.com/UserScape/HelpSpot/pull/1160
            $update = new requestUpdate($msgRequestId, $origReq, 0, __FILE__, __LINE__);
            $update->notify = false; //notify below instead
            $reqResult = $update->checkChanges();

            app('events')->flush('request.history.create');

            // Set staff notification data
            // Set that data here instead of from within apiAddRequestHistory() so that we can send log and body in one email
            // Note: $notificationCreator remains 0 here
            if ($reqHis || isset($reqResult['xRequestHistory'])) {
                //Array if both set else just the ID
                if ($reqHis && isset($reqResult['xRequestHistory'])) {
                    $notificationRequestIds = [$reqHis, $reqResult['xRequestHistory']];	//first is body, second is log
                } else {
                    $notificationRequestIds = isset($reqHis) ? $reqHis : $reqResult['xRequestHistory'];
                }
            }
        } else {
            $oldReq = apiGetRequest($msgRequestId);

            // Add new note. This is done before the triggers so that fullpublichistory is accurate
            $reqHis = apiAddRequestHistory([
                'xPerson'             => $personId,
                'xRequest'            => $msgRequestId,
                'dtGMTChange'         => $msgDate,
                'fPublic'             => $public,
                'tNote'               => $msgMessage,
                'sRequestHistoryHash' => md5(trim($message->getBody())),
                'fNoteIsHTML'         => $message->isHtml(),
                'tEmailHeaders'       => hs_serialize($message->headers),
            ]);

            $update = new requestUpdate($msgRequestId,$origReq,0,__FILE__,__LINE__);
            $update->notify = false; //notify below instead
            $update->skipTrigger = true; // Skip running any triggers since they run below.
            $reqResult = $update->checkChanges();

            //Trigger check
            apiRunTriggers($msgRequestId, $origReq, $oldReq, $msgMessage, $public, $personId, 2, __FILE__, __LINE__);

            app('events')->flush('request.history.create');

            // Set staff notification data
            $notificationCreator = $personId;
            $notificationRequestIds = $reqHis;
            // TODO: Do we want to check if $reqResult['xRequestHistory'] isset and add that as a $notificationRequestIds?
        }

        // If request history added then save documents
        if ($reqHis) {
            $this->logger->display('Note Added ('.$msgRequestId.')');
            // Insert info about attachments to request_history and write out the files to disk
            apiAddDocument($msgRequestId, $msgFiles, $reqHis, __FILE__, __LINE__);
            $this->logger->display('Finished adding documents');
        }

        // Send staff notifications
        if (isset($notificationRequestIds) && ! empty($notificationRequestIds)) {
            $notifier = new hs_notify($msgRequestId, $notificationRequestIds, $notificationCreator, __FILE__, __LINE__);
            $notifier->SetRequestType('existing');
            $notifier->Notify();
        }

        return true;
    }
}
