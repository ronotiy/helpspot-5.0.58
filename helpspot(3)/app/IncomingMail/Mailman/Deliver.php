<?php

namespace HS\IncomingMail\Mailman;

use HS\Merged;
use HS\Mailbox;
use HS\Request;
use HS\IncomingMail\Message;
use Illuminate\Support\Facades\Log;
use HS\IncomingMail\Loggers\MailLogger;
use HS\IncomingMail\Processors\MessageId;
use HS\IncomingMail\Processors\Attachments;

class Deliver
{
    private $logger;

    private $mailboxId;

    protected $mailbox;

    private $transaction;

    /**
     * Deliver constructor.
     * @param Mailbox $mailbox
     * @param MailLogger $logger
     * @param string $transaction
     */
    public function __construct(Mailbox $mailbox, MailLogger $logger, $transaction = '')
    {
        $this->mailbox = $mailbox->toArray();
        $this->mailboxId = $mailbox->xMailbox;
        $this->logger = $logger;
        $this->transaction = $transaction;
    }

    /**
     * @param Message $message
     * @return array|bool|mixed
     */
    public function toDb(Message $message)
    {
        // Is this an internal HelpSpot message?
        if ($message->isHelpSpotMessage(hs_setting('cHD_ORGNAME'))) {
            $this->logger->display('Deleting message matching x-helpspot header');
            Log::debug('['.get_class($this).'] deleting helpspot message (matching x-helpspot header)', [
                'x-helpspot' => $message->headers['x-helpspot'] ?? '',
                'transaction' => $this->transaction,
            ]);

            return false;
        }

        Log::debug('['.get_class($this).'] attempting to deliver message to database', [
            'transaction' => $this->transaction,
        ]);

        // Try and find the original request id
        $messageIdFinder = new MessageId($message, utf8_trim(hs_setting('cHD_EMAILPREFIX')));
        $msgRequestId = $messageIdFinder->find();
        $this->logger->display('ID Finder');

        // Parse Attachments
        $attachments = new Attachments($message, $this->logger);
        $msgFiles = $attachments->process();
        Log::debug('['.get_class($this).'] attachments processed', [
            'processed_attachment_count' => count($msgFiles),
            'transaction' => $this->transaction,
        ]);
        $this->logger->display('attachments');

        // first if id is set make sure it's valid.
        // If it is then treat as existing req, if not check if it's been merged, else a new one
        if (! empty($msgRequestId)) {
            // Lookup info on original request
            $origReq = $this->getRequest($msgRequestId);
            if ($origReq) {
                $msgRequestId = $origReq['xRequest'];
            } else {
                $msgRequestId = '';
            }

            //If this email comes hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE') number of days after the request was closed then consider it new
            if ((trim(hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE')) == 'never' && $origReq && $origReq['fOpen'] == 0)
                || (hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE') != 0 && $origReq && $origReq['fOpen'] == 0 && ! empty($origReq['dtGMTClosed']) && $origReq['dtGMTClosed'] < strtotime('-'.hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE').' Days'))) {
                $this->logger->display('Treat as new request (reply to request older than '.hs_setting('cHD_EMAIL_DAYS_AFTER_CLOSE').' days)');
                $msgRequestId = '';
            }
        }

        if (! empty($msgRequestId) && isset($origReq)) {
            Log::debug('['.get_class($this).'] updating existing request', [
                'xRequest' => $msgRequestId,
                'transaction' => $this->transaction,
            ]);
            $this->logger->display('Update Existing Request');
            // This is an existing request response
            $updater = new UpdateRequest($this->mailbox, $this->logger);

            return $updater->save($message, $msgRequestId, $origReq, $msgFiles);
        } else {
            Log::debug('['.get_class($this).'] creating new request from message', [
                'transaction' => $this->transaction,
            ]);
            $this->logger->display('Create New Request');
            // This is a new message so add it
            $newMessage = new NewRequest($this->mailbox, $this->logger);

            return $newMessage->save($message, $msgFiles);
        }
    }

    public function getRequest($id)
    {
        // Lookup info on original request
        $origReq = Request::find($id);

        //check if request we're looking for was merged and so we should set the new ID to $msgRequestId
        if (! $origReq && $merged = Merged::find($id)) {
            $msgRequestId = $merged->xRequest;
            $origReq = Request::find($msgRequestId);
            $this->logger->display('Original request was merged. Changing request ID to '.$msgRequestId);
        } elseif (! $origReq) {
            return false; // we didn't find anything
        }

        return $origReq->toArray();
    }
}
