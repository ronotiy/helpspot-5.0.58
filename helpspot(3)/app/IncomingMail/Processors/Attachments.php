<?php

namespace HS\IncomingMail\Processors;

use HS\IncomingMail\Message;
use HS\IncomingMail\Loggers\MailLogger;

class Attachments
{
    private $message;

    private $excludeMimeTypes;

    private $logger;

    /**
     * Attachments constructor.
     * @param Message $message
     * @param string $excludeMimeTypes
     * @param MailLogger $logger
     */
    public function __construct(Message $message, MailLogger $logger, $excludeMimeTypes = '')
    {
        $this->message = $message;
        $this->logger = $logger;
        $this->excludeMimeTypes = $excludeMimeTypes;
    }

    /**
     * @return array|bool
     */
    public function process()
    {
        if (! $this->attachmentsAllowed()) {
            return false;
        }

        $msgFiles = [];

        $excludedMimeTypes = $this->getExcludedMimeTypes();

        // Parse the inline ones first
        if (count($this->message->inline_attachments) > 0) {
            $msgFiles = $this->parseInline($excludedMimeTypes, $msgFiles);
        }

        // Now the true attachments
        if (count($this->message->attachments) > 0) {
            $msgFiles = $this->parseAttachment($excludedMimeTypes, $msgFiles);
        }

        return $msgFiles;
    }

    /**
     * @return bool
     */
    protected function attachmentsAllowed()
    {
        return hs_setting('cHD_MAIL_ALLOWMAILATTACHMENTS') == 1;
    }

    /**
     * @param $excludedMimeTypes
     * @param array $msgFiles
     * @return array
     */
    protected function parseInline($excludedMimeTypes, $msgFiles = [])
    {
        //Reverse so they're shown in correct order
        $this->message->inline_attachments = array_reverse($this->message->inline_attachments, true);

        foreach ($this->message->inline_attachments as $file) {
            // Make sure file type isn't on admins exclude list or if list is empty
            $ext = explode('.', $file['name']);
            if ($excludedMimeTypes and in_array($ext[1], $excludedMimeTypes)) {
                // Skip this message
                $this->logger->display('Inline attach ignored - On admin mime exclude list ('.$file['name'].')');
            } else {
                $this->logger->display('Inline Attach: '.$file['name'].':'.$file['mimetype'].' '.mb_strlen($file['body']));
                $msgFiles[] = $file;
            }
        }

        return $msgFiles;
    }

    /**
     * @param $excludedMimeTypes
     * @param array $msgFiles
     * @return array
     */
    public function parseAttachment($excludedMimeTypes, $msgFiles = [])
    {
        //Reverse so they're shown in correct order
        $this->message->attachments = array_reverse($this->message->attachments, true);

        foreach ($this->message->attachments as $file) {
            // Make sure file type isn't on admins exclude list	or the list is empty
            $ext = explode('.', $file['name']);
            if ($excludedMimeTypes and in_array($ext[1], $excludedMimeTypes)) {
                // Skip this message
                $this->logger->display('Attach ignored - On admin mime exclude list ('.$file['name'].')');
            } else {
                $this->logger->display('Attach: '.$file['name'].':'.$file['mimetype'].' '.mb_strlen($file['body']));
                $msgFiles[] = $file;
            }
        }

        return $msgFiles;
    }

    /**
     * @return array|bool
     */
    protected function getExcludedMimeTypes()
    {
        if (trim($this->excludeMimeTypes) != '') {
            return explode(',', $this->excludeMimeTypes);
        }

        return false;
    }
}
