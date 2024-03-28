<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Exceptions\Jobs\PurgeErrorLogs;

class CleanErrorLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'errors:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean out old error logs';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return dispatch_now(new PurgeErrorLogs);
    }
}
