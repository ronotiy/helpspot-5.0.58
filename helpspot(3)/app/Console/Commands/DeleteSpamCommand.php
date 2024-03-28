<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;

use HS\Domain\Workspace\Jobs\DeleteSpam;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class DeleteSpamCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'request:delete-spam {--force : Skip "Are you sure" prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all requests currently marked as spam. This cannot be undone.';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('force') || $this->confirm('Are you sure? This will delete all the spam requests.')) {
            $command = new DeleteSpam();

            if ($count = $command->run()) {
                $this->info($count.' spam deleted.');

                return 0;
            }

            if ($count === 0) {
                $this->info('There was no spam');

                return 0;
            }

            $this->info('<error>Something went wrong. The spam could not be deleted.</error>');

            return 1;
        }
    }
}
