<?php

namespace HS\IncomingMail\Mailman;

use HS\User;
use HS\Mail\SendFrom;
use HS\Jobs\SendMessage;
use HS\IncomingMail\Message;
use HS\Mail\Mailer\MessageBuilder;
use Illuminate\Support\Facades\Log;
use HS\IncomingMail\Processors\Spam;
use HS\IncomingMail\Loggers\MailLogger;
use HS\IncomingMail\Processors\LoopCheck;
use HS\IncomingMail\Processors\ParserTags;

class NewRequest
{
    private $logger;
    private $mailbox;
    private $mailboxId;
    private $loopCheck;

    public function __construct(array $mailbox, MailLogger $logger)
    {
        $this->logger = $logger;
        $this->mailbox = $mailbox;
        $this->mailboxId = $mailbox['xMailbox'];
        $this->loopCheck = new LoopCheck;
    }

    public function save(Message $message, $msgFiles)
    {
        $tags = new ParserTags($message);

        $msgFromCustomerID = $tags->hs_request_id($message->getBody(), hs_setting('cHD_EMAILPREFIX'));
        $parseFirstName = $tags->hs_customer_firstname();
        $parseLastName = $tags->hs_customer_lastname();
        $msgFromPhone = $tags->hs_customer_phone();
        $msgFromEmail = $tags->hs_customer_email();
        $msgCategory = $tags->hs_category();
        $assignedTo = $tags->hs_assigned_to();
        $customFieldValues = $tags->customFields();

        // If we haven't assigned a category from parser tags, we will use the mailbox default.
        if (! $msgCategory) {
            $msgCategory = $this->mailbox['xCategory'];
        }

        //If name found in parser tags then override name from email
        if ($parseFirstName || $parseLastName) {
            $name = [
                'fname' => trim($parseFirstName),
                'lname' => trim($parseLastName),
            ];
        } else {
            $name = parseName($message->getFromName());
        }

        // From Email
        if (! $msgFromEmail) {
            $msgFromEmail = $message->getFromEmail();
        }

        // Loop check. If we received the identical email with the last X minutes then ignore.
        // If 0 seconds set in constant than don't do check
        if (hs_setting('cHD_EMAILLOOP_TIME') > 0) {
            if ($this->loopCheck->newMessageInLoop($message)) {
                $this->logger->display('Message is a duplicate');
                Log::debug('['.get_class($this).'] Message is a duplicate', [
                    'From Email' => $msgFromEmail,
                    'Subject' => $message->getSubject(lg_no_subject),
                ]);
                return false;
            }
        }

        // See if email is from a system user and apply history appropriately
        if (! empty($msgFromEmail)) {
            $person = User::getByEmail($msgFromEmail);
            if ($person) {
                $personId = $person->xPerson;
            }
        }

        // Check for SPAM
        $spam = new Spam($message);
        if ($spam->isSpam() != '0') { // Returns String of zero for some reason.
            $xStatus = hs_setting('cHD_STATUS_SPAM, 2');
            $assignedTo = 0; //Spam should be assigned to nobody
            $this->logger->display('This message is spam');
        } else {
            $xStatus = hs_setting('cHD_STATUS_ACTIVE', 1);
            $this->logger->display('This message is not spam');
        }

        //Add req - Note appending of custom fields on end of array
        $this->logger->display('Adding request');
        $personId = $personId ?? '';
        $dateOpened = date('U');

        $requestData = ['fOpenedVia' => 1,
                'mode' => 'add',
                'xOpenedViaId' => $this->mailboxId,
                'xMailboxToSendFrom' => $this->mailboxId,
                'xPersonOpenedBy' => $personId,
                'xCategory' => $msgCategory ?? '',
                'xPersonAssignedTo' => $assignedTo ?? '',
                'dtGMTOpened' => $dateOpened,
                'sTitle' => $message->getSubject(lg_no_subject),
                'tBody' => utf8_trim($message->getBody()),
                'fNoteIsHTML' => $message->isHtml(),
                'fNoteIsClean' => true,
                'tEmailHeaders' => hs_serialize($message->headers),
                'xStatus' => $xStatus,
                'fPublic' => 1,
                'fUrgent' => $message->isImportant() ? 1 : false,
                'sFirstName' => $name['fname'],
                'sLastName' => $name['lname'],
                'sUserId' => $msgFromCustomerID ?? '',
                'sPhone' => $msgFromPhone ?? '',
                'sEmail' => $msgFromEmail, ];

        $requestData = array_merge($requestData, $customFieldValues);

        $reqRes = apiAddEditRequest($requestData, 1, __FILE__, __LINE__);

        // if the request wasn't added then bail out
        if (! $reqRes) {
            $this->logger->display('Could not add request. Check the error log in admin');
            return false;
        }

        $msgRequestId = $reqRes['xRequest'];
        $this->logger->display('Request created ('.$msgRequestId.')');

        // Insert info about attachments to request_history and write out the files to db
        apiAddDocument($msgRequestId, $msgFiles, $reqRes['xRequestHistory'], __FILE__, __LINE__);
        $this->logger->display('Adding documents');

        // Auto Response?
        $this->autoResponse($msgRequestId, $xStatus, $message, $msgFromEmail);

        //MAIL RULES
        $rules = $this->getMailRules();
        if (is_array($rules)) {
            foreach ($rules as $k=>$rule) {
                $isSpam = ($xStatus == hs_setting('cHD_STATUS_SPAM', 2));
                $rule->ApplyRule($msgRequestId, $reqRes['xRequestHistory'], $message, $this->mailbox, $isSpam, $this->logger, $msgFromCustomerID);
            }
        }

        return $msgRequestId;
    }

    /**
     * @param $msgRequestId
     * @param $xStatus
     * @param Message $message
     * @param $msgFromEmail
     * @return bool
     */
    public function autoResponse($msgRequestId, $xStatus, Message $message, $msgFromEmail)
    {
        if ($this->mailbox['fAutoResponse'] == 0 || $xStatus == hs_setting('cHD_STATUS_SPAM', 2)) {
            return false;
        }

        // If this is a delivery error then bail out.
        if ($message->isDeliverError()) {
            return false;
        }

        //Check that there hasn't been a high volume of requests from one address over a short time span.
        //This could be a stuck loop, in which case we do not want to send the reply so it breaks the loop.
        if ($this->loopCheck->shouldAutoRespond($msgFromEmail) > hs_setting('cHD_EMAIL_LOOPCHECK_CTMAX')) {
            $this->logger->display('Message flagged as a loop. Auto reply is skipped to break the loop.');
            errorLog(sprintf(lg_autoreply_loop_break, $msgFromEmail), 'Email Importing', __FILE__, __LINE__);
            return false;
        }

        $this->logger->display('Sending auto reply.');

        //Get request details
        $req_details = apiGetRequest($msgRequestId);

        $sendFrom = new SendFrom($this->mailbox['sReplyEmail'], replyNameReplace($this->mailbox['sReplyName'], $req_details['xPersonAssignedTo']), $this->mailbox['xMailbox']);
        $messageBuilder = (new MessageBuilder($sendFrom, $msgRequestId))
            ->to($msgFromEmail)
            ->setType('public')
            ->setSubject(apiSetSubjectPrefix(lg_mailre, $message->getSubject(lg_no_subject)).' {'.trim(hs_setting('cHD_EMAILPREFIX')).$msgRequestId.'}')
            ->body('tAutoResponse',  '', getPlaceholders([], $req_details));

        SendMessage::dispatch($messageBuilder, $attachments=null, $publicEmail=true)
            ->onQueue(config('queue.high_priority_queue')); // mail.public

        return true;
    }

    /**
     * @return array
     */
    protected function getMailRules()
    {
        $rules = [];
        $mailRules = apiGetMailRules(0);
        if (is_object($mailRules) && $mailRules->RecordCount() > 0) {
            while ($r = $mailRules->FetchRow()) {
                $rules[$r['fOrder']] = hs_unserialize($r['tRuleDef']);
            }
        }

        return $rules;
    }
}
