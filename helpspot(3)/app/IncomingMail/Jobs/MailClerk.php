<?php

namespace HS\IncomingMail\Jobs;

use usLicense;
use HS\Mailbox;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;

class MailClerk extends MailJobBase
{
    use Queueable;

    protected $debug;

    private array $mailbox_ids;

    /**
     * Create a new job instance.
     *
     * @param array $mailbox_ids
     */
    public function __construct($mailbox_ids = [])
    {
        $this->mailbox_ids = $mailbox_ids;
    }

    /**
     * Grab the mail and dispatch to FetchMail.
     */
    public function handle()
    {
        $this->loadTheWorld();

        $this->debug = hs_setting('cHD_TASKSDEBUG', false);

        $this->setLicense();

        $mailboxes = Mailbox::getActive();

        foreach ($mailboxes as $box) {

            //Only do specific mailbox if IDs are passed in
            if (count($this->mailbox_ids) > 0 && ! in_array($box->xMailbox, $this->mailbox_ids)) {
                continue;
            }

            $transaction = Str::uuid()->toString();
            Log::debug('['.get_class($this).'] Dispatching FetchMail', [
                'xMailbox' => $box->xMailbox,
                'transaction' => $transaction,
            ]);

            FetchMail::dispatch($box, $transaction); // mail.incoming
        }

        return true;
    }

    /**
     * Set the global license.
     */
    protected function setLicense()
    {
        $licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
        $GLOBALS['license'] = $licenseObj->getLicense();
    }
}
