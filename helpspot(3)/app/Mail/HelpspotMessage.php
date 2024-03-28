<?php

namespace HS\Mail;

use HS\Mail\Mailer\MessageBuilder;

use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Queue\SerializesModels;

class HelpspotMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var MessageBuilder
     */
    protected $builder;

    /**
     * @var Attachments
     */
    protected $attachmentsToClean;

    /**
     * Create a new message instance.
     *
     * @param MessageBuilder $builder
     */
    public function __construct(MessageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $builder = $this->subject($this->builder->getSubject())
            ->from($this->builder->getFrom('email'), $this->builder->getFrom('name'))
            ->to($this->builder->getTo())
            ->text('mail.helpspot-message-text', ['plain' => $this->builder->getText()])
            ->view('mail.helpspot-message-html', ['html' => $this->builder->getHtml()]);

        if( $this->builder->getCC() ) {
            $builder->cc($this->builder->getCC());
        }

        //If the system is set to send a global bCC let's do it
        if(! hs_empty(hs_setting('cHD_EMAIL_GLOBALBCC'))) {
            if((hs_setting('cHD_EMAIL_GLOBALBCC_TYPE') == "public" && $this->builder->isPublicEmail()) || hs_setting('cHD_EMAIL_GLOBALBCC_TYPE') == "all") {
                $builder->bcc(trim(hs_setting('cHD_EMAIL_GLOBALBCC')));
            }
        }

        if( $this->builder->getBCC() ) {
            $builder->bcc($this->builder->getBCC());
        }

        return $builder;
    }

    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function send($mailer)
    {
        return $this->withLocale($this->locale, function () use ($mailer) {
            Container::getInstance()->call([$this, 'build']);

            $result = $mailer->send($this->buildView(), $this->buildViewData(), function (Message $message) {
                $this->buildFrom($message)
                    ->buildRecipients($message)
                    ->buildSubject($message)
                    ->runCallbacks($message)
                    ->buildAttachments($message)
                    ->registerHSAttachments($message->getAttachments());
            });

            $this->cleanStorageAttachments();

            return $result;
        });
    }

    public function registerHSAttachments(Attachments $attachments)
    {
        $this->attachmentsToClean = $attachments;
    }

    public function cleanStorageAttachments()
    {
        if( $this->attachmentsToClean instanceof Attachments ) {
            $this->attachmentsToClean->cleanup();
        }
    }
}
