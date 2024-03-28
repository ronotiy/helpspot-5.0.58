<?php

namespace HS\Console\Commands;

use HS\LoginAttempts;
use Illuminate\Console\Command;

class DeleteLoginHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:loginhistory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all login history items over 3 months old.';

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
        return LoginAttempts::where('dtDateAdded', '<', strtotime('-3 months'))->delete();
    }
}
