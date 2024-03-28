<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Domain\Filters\Jobs\PurgeFilterPerformance;

class CleanFilterPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filters:clean-perf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old filter performance data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return dispatch_now(new PurgeFilterPerformance);
    }
}
