<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Base\ValidationException;

use HS\License\Jobs\AddUpdateLicense;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class AddLicenseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'license {--license-file= : License File Path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or update your HelpSpot license.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $stdErr = $this->getOutput()->getErrorStyle();

        $licenseFilePath = (!$this->option('license-file') ? $this->ask(lg_inst_licensefile_cli.': ') : $this->option('license-file'));

        /*
         * Update or add the license
         */
        try {
            dispatch_now(new AddUpdateLicense($licenseFilePath));
            /*
             * Else print out the errors, depending on
             * the type of exception we get
             */
        } catch (ValidationException $e) {
            $messages = $e->getErrors();

            foreach ($messages->all() as $message) {
                $stdErr->writeln('<error>'.$e->getMessage().'</error>');
            }

            return 1;
        } catch (\Exception $e) {
            $stdErr->writeln('<error>'.$e->getMessage().'</error>');

            return 1;
        }

        return 0;
    }
}
