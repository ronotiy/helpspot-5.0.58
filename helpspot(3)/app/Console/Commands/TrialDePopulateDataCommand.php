<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;

use HS\Trials\PopulateDataCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class TrialDePopulateDataCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'trial:depopulate {--force : Skip "Are you sure" prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DESTRUCTIVE! Remove all data from a trial installation.';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $stdErr = $this->getOutput()->getErrorStyle();

        if ($this->option('force') || $this->confirm('Are you sure? This will destroy all data added since installation.')) {
            $command = new PopulateDataCommand();

            if ($command->depopulate()) {
                $stdErr->writeln('Data removed!');

                return 0;
            }

            $stdErr->writeln('<error>Something went wrong. Perhaps this is not a trial license, or the database is not freshly installed.</error>');

            return 1;
        }
    }
}
