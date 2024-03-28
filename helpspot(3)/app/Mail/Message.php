<?php

namespace HS\Mail;

use Illuminate\Mail\Message as LaravelMessage;

class Message extends LaravelMessage
{
    protected $attachments;

    /**
     * @param Attachments $attachments
     * @return $this
     */
    public function attachments(Attachments $attachments)
    {
        $this->attachments = $attachments;

        foreach($this->attachments as $attachment) {
            $this->attachment($attachment);
        }

        return $this;
    }

    public function attachment(Attachment $attachment)
    {
        if( $attachment->isEmbed() )
        {
            $this->swift->embed($attachment->toSwift());
        } else {
            $this->swift->attach($attachment->toSwift());
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttachments()
    {
        return $this->attachments;
    }
}
