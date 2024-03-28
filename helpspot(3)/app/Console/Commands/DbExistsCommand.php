<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Database\Jobs\CheckDbExists;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DbExistsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'db:exists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check that a HelpSpot database exists.';

    /**
     * @var \HS\CommandBus\CommandBusInterface
     */
    protected $bus;

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $stdErr = $this->getOutput()->getErrorStyle();

        try {
            // Check default connection, as defined in .env
            $dbExists = dispatch_now(new CheckDbExists());
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }
        }

        if ($dbExists) {
            $this->info('Database exists and HelpSpot is installed.');

            return 0;
        }

        $stdErr->writeln('<error>Database does not exist or HelpSpot is not installed.</error>');

        return 1;
    }
}
