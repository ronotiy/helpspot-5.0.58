<?php

namespace HS\Mail;

use Illuminate\Mail\Mailer as LaravelMailer;

class Mailer extends LaravelMailer
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * @param Attachments $attachments
     * @return $this
     */
    public function withAttachments(Attachments $attachments)
    {
        $this->getMessage()->attachments($attachments);

        return $this;
    }

    /**
     * Public accessor for getting a message object
     * @return Message
     */
    public function getMessage()
    {
        if( $this->message ) {
            return $this->message;
        }

        return $this->message = $this->createMessage();
    }

    /**
     * Create a new message instance.
     *
     * @return \Illuminate\Mail\Message
     */
    protected function createMessage()
    {
        if( $this->message ) {
            return $this->message;
        }

        $message = new Message($this->swift->createMessage('message'));

        $headers = $message->getHeaders();
        $headers->addTextHeader('X-HelpSpot', md5(hs_setting('cHD_ORGNAME')));
        $headers->addTextHeader('X-Mailer', 'HelpSpot v' . hs_setting('cHD_VERSION'));

        // If a global from address has been specified we will set it on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We'll just go ahead and push this address.
        if (! empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push this address.
        if (! empty($this->replyTo['address'])) {
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        return $message;
    }
}
