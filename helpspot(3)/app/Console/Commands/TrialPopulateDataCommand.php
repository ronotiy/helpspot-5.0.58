<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;

use HS\Trials\PopulateDataCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class TrialPopulateDataCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'trial:populate {--force : Skip "Are you sure" prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate a new empty trial with fake data for testing purposes.';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $stdErr = $this->getOutput();

        if ($this->getOutput() instanceof ConsoleOutputInterface) {
            $stdErr = $this->getOutput()->getErrorStyle();
        }

        if ($this->option('force') || $this->confirm('Are you sure? This will add fake test data to your installation.')) {
            $command = new PopulateDataCommand();

            if ($command->run()) {
                $stdErr->writeln('Data populated!');

                return 0;
            }

            $stdErr->writeln('<error>Something went wrong. Perhaps this is not a trial license, or the database is not freshly installed.</error>');

            return 1;
        }
    }
}
