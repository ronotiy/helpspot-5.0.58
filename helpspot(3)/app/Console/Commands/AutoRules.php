<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Automation\Jobs\AutomationRules;

class AutoRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:rules {--id=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run automation rules';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $automationIds = $this->parseMailboxIds();

        return dispatch_now(new AutomationRules($automationIds));
    }

    /**
     * Grab array of automation rule IDs and also parse
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
