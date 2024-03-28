<?php

namespace HS\Console\Commands;

use DB;
use HS\Base\MessageBagException;
use HS\Install\Updater\Jobs\RunUpdates;
use HS\Install\Updater\Jobs\CheckIsSupported;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class UpdateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'update {--y|yes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update HelpSpot';

    /**
     * Install Language.
     * @var string
     */
    protected $language;

    /**
     * Used db connection type.
     * @var string
     */
    protected $defaultConnection;

    /**
     * Translated configuration error messages.
     * @var array
     */
    protected $validationErrors = [];

    /**
     * Execute the console command.
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $stdErr = $this->getOutput()->getErrorStyle();

        $this->loadDependencies();

        $continue = $this->option('yes');

        $this->setLanguage('english-us');
        $this->loadLanguage();

        $this->setupTranslations();

        $this->info("\nWelcome to the HelpSpot Updater!\n");

        /*
         * Ensure license is not expired
         */
        $this->info("\nChecking License...");

        try {
            $license = dispatch_now(new CheckIsSupported());
        } catch (ValidationException $e) {
            foreach ($e->getErrors()->all() as $error) {
                $stdErr->writeln('<error>'.$error.'</error>');
            }

            return 1;
        }
        $this->info("License OK\n");

        /**
         * Capture version in database ("current")
         * vs file version ("upgrade to").
         */
        $fromVersion = DB::table('HS_Settings')->where('sSetting', 'cHD_VERSION')->first();
        $fromVersion = $fromVersion->tValue;

        // Don't update if its the same version
        if ($fromVersion == instHSVersion) {
            $stdErr->writeln('<error>Aborting: Version '.$fromVersion.' is the same as currently installed version '.instHSVersion.'</error>');

            return 1;
        }

        // Abort if this is a jump from version 3 to version 4
        $majorVersionFrom = substr($fromVersion, 0, 1);
        $majorVersionTo = substr(instHSVersion, 0, 1);
        if ($majorVersionFrom == '3' && $majorVersionTo == '4') {
            $stdErr->writeln('<error>Aborting: Converting from HelpSpot 3 to HelpSpot 4 requires a database conversion</error>');
            $this->info("For documentation on converting from HelpSpot 3 to HelpSpot 4, please see\n https://www.helpspot.com/helpdesk/index.php?pg=kb.page&id=463");

            return 1;
        }

        /*
         * Determine (or ask) what versions updating from/to
         */
        if (! $continue) {
            $this->confirm('Updating from version '.$fromVersion.' to '.instHSVersion.'. Is this correct?');
        }

        try {
            dispatch_now(new RunUpdates($fromVersion, instHSVersion));
        } catch (MessageBagException $e) {
            $stdErr->writeln('<error>Whoops! Some errors occurred during update:</error>');

            foreach ($e->getErrors()->all() as $error) {
                $stdErr->writeln('<error>'.$error.'</error>');
            }

            $stdErr->writeln('<error>If you need help, you can find us at customer.service@userscape.com</error>');

            return 1;
        } catch (\Exception $e) {
            $stdErr->writeln('<error>Whoops! An error occurred during update:\n'.$e->getMessage().'</error>');
            $stdErr->writeln('<error>If you need help, you can find us at customer.service@userscape.com</error>');
            Log::error($e);

            return 1;
        }

        return 0;
    }

    protected function loadLanguage()
    {
        return new \language('installer', $this->getLanguage());
    }

    protected function getLanguage()
    {
        if (is_null($this->language)) {
            return 'english-us';
        }

        return $this->language;
    }

    protected function setLanguage($language)
    {
        $this->language = $language;
    }

    protected function loadDependencies()
    {
        $this->defaultConnection = config('database.default');
    }

    protected function setupTranslations()
    {
        // Needed?
    }
}
