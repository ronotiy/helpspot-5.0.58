<?php

namespace HS\Mail\Mailer;

use HS\Mail\SendFrom;
use Facades\HS\View\Mail\TemplateParser;

class MessageBuilder
{
    /**
     * @var SendFrom
     */
    protected $sendFrom;

    /**
     * @var int|null
     */
    protected $requestId;

    /**
     * @var array [['name' => 'foo', 'address' => 'bar'], ...]
     */
    protected $to;

    /**
     * @var array ['name' => 'foo', 'address' => 'bar']
     */
    protected $from;

    /**
     * @var array [['name' => 'foo', 'address' => 'bar'], ...]
     */
    protected $cc;

    /**
     * @var array [['name' => 'foo', 'address' => 'bar'], ...]
     */
    protected $bcc;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $bodyHtml;

    /**
     * @var string
     */
    protected $bodyText;

    /**
     * public / staff
     * @var string
     */
    protected $emailType = 'staff';

    /**
     * Message constructor.
     * @param SendFrom $sendFrom
     * @param $requestId
     */
    public function __construct(SendFrom $sendFrom, $requestId=null)
    {
        $this->sendFrom = $sendFrom;
        $this->requestId = $requestId;

        $this->from($this->sendFrom->email(), $this->sendFrom->name());
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getTo($property=null)  {
        if( $property ) {
            return $this->to[0][$property] ?? null;
        }
        return $this->to;
    }

    public function getFrom($property=null) {
        if( $property ) {
            return $this->from[$property] ?? null;
        }
        return $this->from;
    }

    public function getCC() {
        return $this->cc;
    }

    public function getBCC() {
        return $this->bcc;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function getHtml() {
        return $this->bodyHtml;
    }

    public function getText() {
        return $this->bodyText;
    }

    /**
     * Set the "tos".
     * We can pass it:
     * 1. A string
     * 2. An array of email strings
     * 3. An array of arrays in format [['foo@example.com', 'bar name'],] - Note that the email address is array index 0
     * 4. An array of arrays in format [['name' => 'foo name', 'email' => 'bar@example.com'],]
     * @param $tos
     * @return $this
     */
    public function to($tos)
    {
        if( ! is_array($tos) ) {
            $tos = [$tos];
        }

        $this->to = collect($tos)->filter(function($item) {
            return ! empty($item);
        })->map(function($item) {
            if( is_array($item) ) {
                return isset($item['email'])
                    ? ['name' => $item['name'] ?? null, 'email' => $item['email']]
                    : ['name' => $item[1] ?? null, 'email' => trim($item[0])];
            }
            return ['name' => null, 'email' => trim($item)];
        })->toArray();

        return $this;
    }

    /**
     * @param string $email
     * @param null $name
     * @return $this
     */
    public function addTo(string $email, $name=null)
    {
        $this->to[] = [
            'name' => $name,
            'email' => $email,
        ];

        return $this;
    }

    public function from($email, $name=null) {
        $this->from = [
            'name' => $name,
            'email' => $email,
        ];

        return $this;
    }

    /**
     * * Set the "CCs".
     * We can pass it:
     * 1. A string
     * 2. An array of email strings
     * 3. An array of arrays in format [['foo@example.com', 'bar name'],] - Note that the email address is array index 0
     * 4. An array of arrays in format [['name' => 'foo name', 'email' => 'bar@example.com'],]
     * @param $ccs
     * @return $this
     */
    public function cc($ccs)
    {
        if( ! is_array($ccs) ) {
            $ccs = [$ccs];
        }

        $this->cc = collect($ccs)->filter(function($item) {
            return ! empty($item);
        })->map(function($item) {
            if( is_array($item) ) {
                return isset($item['email'])
                    ? ['name' => $item['name'] ?? null, 'email' => $item['email']]
                    : ['name' => $item[1] ?? null, 'email' => trim($item[0])];
            }
            return ['name' => null, 'email' => trim($item)];
        })->toArray();

        return $this;
    }

    public function addCC($email, $name=null)
    {
        $this->cc[] = [
            'name' => $name,
            'email' => $email,
        ];

        return $this;
    }

    /**
     * Set the "BCCs".
     * We can pass it:
     * 1. A string
     * 2. An array of email strings
     * 3. An array of arrays in format [['foo@example.com', 'bar name'],] - Note that the email address is array index 0
     * 4. An array of arrays in format [['name' => 'foo name', 'email' => 'bar@example.com'],]
     * @param $bcs
     * @return $this
     */
    public function bcc($bcs)
    {
        if( ! is_array($bcs) ) {
            $bcs = [$bcs];
        }

        $this->bcc = collect($bcs)->filter(function($item) {
            return ! empty($item);
        })->map(function($item) {
            if( is_array($item) ) {
                return isset($item['email'])
                    ? ['name' => $item['name'] ?? null, 'email' => $item['email']]
                    : ['name' => $item[1] ?? null, 'email' => trim($item[0])];
            }
            return ['name' => null, 'email' => trim($item)];
        })->toArray();

        return $this;
    }

    public function addBcc($email, $name=null)
    {
        $this->bcc[] = [
            'name' => $name,
            'email' => $email,
        ];

        return $this;
    }

    /**
     * Set the Subject directly, without any template logic
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param $template
     * @param $vars
     * @return MessageBuilder
     */
    public function subject($template, $vars)
    {
        return $this->setSubject(TemplateParser::subject($template, $vars, $this->mailboxId()));
    }

    /**
     * @param $html
     * @return $this
     */
    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;
        return $this;
    }

    public function setBodyText($text)
    {
        $this->bodyText = $text;
        return $this;
    }

    /**
     * @param $template
     * @param array $data
     * @param $message
     * @return $this
     */
    public function body($template, $message='', $data=[])
    {
        $content = TemplateParser::body($template, $data, $this->mailboxId(), $message);
        $this->setBodyHtml($content['html']);
        $this->setBodyText($content['text']);

        return $this;
    }

    public function mailboxId()
    {
        return $this->sendFrom->mailbox();
    }

    /**
     * Is this a public email? Used for global BCC
     * @return bool
     */
    public function isPublicEmail()
    {
        return $this->emailType == 'public';
    }

    /**
     * What type of email is this? public or staff? used for global BCC
     * @param string $type
     * @return MessageBuilder
     */
    public function setType($type = 'staff')
    {
        $this->emailType = $type;

        return $this;
    }
}
