<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\IncomingMail\Jobs\MailClerk;

class CollectMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:check {--id=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Email Mailboxes for new mail';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! defined('IN_PORTAL')) {
            define('IN_PORTAL', false);
        }

        $mailboxIds = $this->parseMailboxIds();

        return dispatch_now(new MailClerk($mailboxIds));
    }

    /**
     * Grab array of mailbox IDs and also parse
     * each to be backwards compatible with helpspot
     * version 4 `--id` option style
     * e.g. new style: --id=1 --id=2 --id=3
     *      old style: --id=1,2,3.
     */
    protected function parseMailboxIds()
    {
        $ids = $this->option('id');

        array_walk($ids, function (&$item) {
            $item = explode(',', $item);
        });

        return collect($ids)->flatten()->map(function ($value) {
            return trim($value);
        })->toArray();
    }
}
