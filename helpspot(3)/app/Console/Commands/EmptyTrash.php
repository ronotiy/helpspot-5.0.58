<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Domain\Workspace\Jobs\DeleteTrash;

class EmptyTrash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:delete-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empty trash if at or older than Trash Delete Delay setting';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return dispatch_now(new DeleteTrash);
    }
}
