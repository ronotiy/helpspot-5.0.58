<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Domain\Workspace\Jobs\CheckReminders as CheckRemindersJob;

class CheckReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and send any request reminders';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if( ! defined('IN_PORTAL') ) {
            define('IN_PORTAL', false);
        }

        return dispatch_now(new CheckRemindersJob);
    }
}
