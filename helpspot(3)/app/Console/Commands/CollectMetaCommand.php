<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Meta\Jobs\CollectInstallationMetadata;

class CollectMetaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meta:gather';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send anonymous HelpSpot environment data to HelpSpot';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dispatch_now(new CollectInstallationMetadata);
    }
}
