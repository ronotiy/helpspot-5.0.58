<?php

namespace HS\IncomingMail\Processors;

use Illuminate\Support\Str;
use HS\IncomingMail\Message;

class MessageId
{
    private $message;

    private $emailPrefix;

    /**
     * Attachments constructor.
     * @param Message $message
     * @param string $emailPrefix
     */
    public function __construct(Message $message, $emailPrefix = '')
    {
        $this->message = $message;
        $this->emailPrefix = $emailPrefix;
    }

    /**
     * @return bool|mixed
     */
    public function find()
    {
        // first search the subject then fallback to error report
        if ($id = $this->searchSubject($this->message->getSubject())) {
            return $id;
        } elseif ($id = $this->errorReport()) {
            return $id;
        }

        return false;
    }

    /**
     * Search the subject for the id.
     *
     * @param $subject
     * @return bool|mixed
     */
    public function searchSubject($subject)
    {
        if (preg_match("/{(".trim($this->emailPrefix)."\d{1,11})/", $subject, $match)) {
            //Strip letters which are a prefix
            return str_replace(trim($this->emailPrefix), '', $match[1]);
        }

        return false;
    }

    /**
     * If it's an error report, find the id.
     *
     * @return bool
     */
    public function errorReport()
    {
        $header = $this->message->headers['content-type'];
        if (Str::contains($header, 'multipart/report') && Str::contains($header, 'delivery-status')) {
            $search = $this->message->getBody();
            foreach ($this->message->inline_attachments as $file) {
                $search = $search.' '.$file['body'];
            }
            foreach ($this->message->attachments as $file) {
                $search = $search.' '.$file['body'];
            }

            preg_match("/index.php\?pg=request.check&id=(\d{1,11})/", $search, $match_url);
            preg_match("/{(".trim($this->emailPrefix)."\d{1,11})}/", $search, $match_norm);

            if ($match_url and $match_url[1]) {
                return $match_url[1];
            //if($debug) echo ">>>> Message: #$mid - Matched Bounced Email to Request: " . $match_url[1] . "\n";
            } elseif ($match_norm and $match_norm[1]) {
                return $match_norm[1];
                //if($debug) echo ">>>> Message: #$mid - Matched Bounced Email to Request: " . $match_norm[1] . "\n";
            }
        }

        return false;
    }
}
