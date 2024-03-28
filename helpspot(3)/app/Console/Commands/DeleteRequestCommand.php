<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;

use HS\Domain\Workspace\Jobs\DeleteRequest;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class DeleteRequestCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'request:delete {--id= : Request ID number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a request instantly. This cannot be undone.';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $id = $this->option('id');

        if ($id) {
            $command = new DeleteRequest($id);

            if ($command->run()) {
                $this->info('Request deleted.');

                return 0;
            }

            $this->info('<error>Something went wrong. The request could not be deleted.</error>');

            return 1;
        } else {
            $this->info('Please provide an ID via the --id option.');
        }
    }
}
