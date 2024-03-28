<?php

namespace HS\Mail\Mailer;


use HS\Base\Gettable;
use Illuminate\Support\Arr;

class SMTP
{
    use Gettable;

    private $host;
    private $port;
    private $fromAddress;
    private $fromName;
    private $encryption;
    private $username;
    private $password;
    private $auth;
    private $helo;
    private $timeout;

    /**
     * SMTP constructor.
     * @param $host
     * @param $port
     * @param $fromAddress
     * @param $fromName
     * @param $encryption
     * @param $username
     * @param $password
     * @param bool $auth
     * @param null|string $helo
     * @param null|int $timeout
     */
    public function __construct($host, $port, $fromAddress, $fromName, $encryption, $username, $password, $auth=true, $helo=null, $timeout=null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->auth = $auth;
        $this->helo = $helo;
        $this->timeout = $timeout;
    }

    public static function fromDefault($fromEmail=null, $fromName=null)
    {
        if( config()->get('mail.default') != 'smtp' ) {
            throw new \Exception('Mail driver is not SMTP');
        }

        return new static(
            config('mail.mailers.smtp.host'),
            config('mail.mailers.smtp.port'),
            $fromEmail ?? config('mail.from.address'),
            $fromName ?? config('mail.from.name'),
            config('mail.mailers.smtp.encryption'),
            config('mail.mailers.smtp.username'),
            config('mail.mailers.smtp.password'),
            config('mail.mailers.smtp.timeout')
        );
    }

    public static function fromHelpSpotSettings($smtpSettings)
    {

        // Set these to null if we aren't authenticating
        $doAuth = Arr::get($smtpSettings, 'cHD_MAIL_SMTPAUTH');
        $username = ($doAuth) ? Arr::get($smtpSettings, 'cHD_MAIL_SMTPUSER') : null;
        $password = ($doAuth) ? Arr::get($smtpSettings, 'cHD_MAIL_SMTPPASS') : null;

        return new static(
            Arr::get($smtpSettings, 'cHD_MAIL_SMTPHOST'),
            Arr::get($smtpSettings, 'cHD_MAIL_SMTPPORT'),
            Arr::get($smtpSettings, 'cHD_MAIL_SMTPUSER'),
            config('mail.from.name'),
            Arr::get($smtpSettings, 'cHD_MAIL_SMTPPROTOCOL'),
            $username,
            $password,
            $doAuth,
            null,
            Arr::get($smtpSettings, 'cHD_MAIL_SMTPTIMEOUT', null)
        );
    }
}
