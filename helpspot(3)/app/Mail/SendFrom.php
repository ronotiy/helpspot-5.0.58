<?php

namespace HS\Mail;


use HS\Base\Gettable;

class SendFrom
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $mailboxId;

    /**
     * SendFrom constructor.
     * @param $email
     * @param $name
     * @param $mailboxId
     */
    public function __construct($email, $name, $mailboxId)
    {
        $this->email = $email;
        $this->name = $name;
        $this->mailboxId = $mailboxId;
    }

    public function email()
    {
        return $this->email;
    }

    public function name()
    {
        return $this->name;
    }

    public function mailbox()
    {
        return $this->mailboxId;
    }

    public static function fromRequestForm($emailFrom, $xPersonAssigned)
    {
        $from = explode('*', $emailFrom);
        return new static($from[1], replyNameReplace($from[0], $xPersonAssigned), $from[2]);
    }

    public static function default()
    {
        return new static(hs_setting('cHD_NOTIFICATIONEMAILACCT'), hs_setting('cHD_NOTIFICATIONEMAILNAME'), 0);
    }
}
