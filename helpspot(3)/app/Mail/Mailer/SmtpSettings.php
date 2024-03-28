<?php

namespace HS\Mail\Mailer;


class SmtpSettings
{
    /**
     * @var null|int
     */
    private $mailbox;

    /**
     * SmtpSettings constructor.
     * @param null $mailbox
     */
    public function __construct($mailbox=null)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * @return SMTP
     * @throws \Exception
     */
    public function settings()
    {
        return ($this->mailbox)
            ? $this->customSmtp()
            : $this->defaultSmtp();
    }

    /**
     * @return SMTP
     * @throws \Exception
     */
    protected function customSmtp()
    {
        if (! isset($GLOBALS['mailbox_smtp_settings'][$this->mailbox])) {
            $mailbox = $GLOBALS['DB']->GetOne('SELECT sSMTPSettings FROM HS_Mailboxes WHERE xMailbox = ?', [$this->mailbox]);
            $GLOBALS['mailbox_smtp_settings'][$this->mailbox] = (hs_empty($mailbox) ? false : hs_unserialize($mailbox));
        }

        if( $GLOBALS['mailbox_smtp_settings'][$this->mailbox] ) {
            return SMTP::fromHelpSpotSettings($GLOBALS['mailbox_smtp_settings'][$this->mailbox]);
        }

        return $this->defaultSmtp();
    }

    /**
     * @return SMTP
     * @throws \Exception
     */
    protected function defaultSmtp()
    {
        return SMTP::fromDefault();
    }
}
