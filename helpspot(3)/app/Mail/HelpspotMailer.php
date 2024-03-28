<?php

namespace HS\Mail;

use Swift_Mailer;
use Swift_SmtpTransport;

use HS\Mail\Mailer\SMTP;
use HS\Mail\Mailer\SmtpSettings;


class HelpspotMailer
{
    /**
     * @param $mailboxId
     * @return Mailer
     * @throws \Exception
     */
    public static function via($mailboxId)
    {
        if( $mailboxId instanceof SMTP ) {
            $smtp = $mailboxId;
        } else {
            $smtp = (new SmtpSettings($mailboxId))
                ->settings();
        }

        $swift = with(new Swift_SmtpTransport($smtp->host, $smtp->port, $smtp->encryption), function($transport) use($smtp) {
            if( $smtp->auth ) {
                $transport->setUsername($smtp->username);
                $transport->setPassword($smtp->password);
            }
            return new Swift_Mailer($transport);
        });

        return with(new Mailer('smtp', app('view'), $swift, app('events')), function(Mailer $mailer) {
            $mailer->setQueue(app('queue'));
            return $mailer;
        });
    }
}
