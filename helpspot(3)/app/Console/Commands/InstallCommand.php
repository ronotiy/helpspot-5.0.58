<?php

namespace HS\Console\Commands;

use HS\Install\Timezone\Tz;
use Illuminate\Console\Command;
use HS\Base\ValidationException;
use Illuminate\Support\MessageBag;
use HS\Database\Jobs\CheckDbExists;

use Illuminate\Support\Facades\Artisan;
use HS\Install\Installer\Jobs\Configure;
use HS\Install\Installer\Jobs\CheckSystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'install
        {--agree : Agree to HelpSpot\'s License Terms}
        {--name= : Full Name (first name last name)}
        {--admin-email= : Admin User Email Address}
        {--password= : Admin User Password}
        {--company= : Company/Help Desk Name}
        {--timezone= : Installation Time Zone}
        {--customer-id= : Customer ID}
        {--license-file= : License File Path}
        {--notification-email= : Address used as the "from" address for notifications}
        {--support-type= : Is HelpSpot used for internal or external use}
        {--ask-smtp : Ask for SMTP input despite server type (default: asks only on Windows)}
        { --smtp-host= : SMTP Host }
        { --smtp-port= : SMTP Port }
        { --smtp-user= : SMTP Username }
        { --smtp-password= : SMTP Password }
        { --smtp-protocol= : SMTP Protocol }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install HelpSpot.';

    /**
     * Install Language.
     * @var string
     */
    protected $language;

    /**
     * #var \HS\System\Features.
     */
    protected $features;

    /**
     * Used db connection type.
     * @var string
     */
    protected $defaultConnection;

    /**
     * System checks and their translated error messages.
     * @var array
     */
    protected $systemChecks = [];

    /**
     * Translated configuration error messages.
     * @var array
     */
    protected $validationErrors = [];

    /**
     * Create a new command instance.
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
        $stdErr = $this->getOutput()->getErrorStyle();

        $this->loadDependencies();

        $this->setLanguage('english-us');
        $this->loadLanguage();

        $this->setupTranslations();

        $this->info("\nWelcome! Beginning Installation Process...\n");

        /*
         * Check if an installation already exists
         */

        if ($this->isInstalled()) {
            $stdErr->writeln('<error>HelpSpot is already installed!</error>');

            return 1;
        }

        /**
         * Agree to Terms.
         */
        $agree = $this->option('agree');

        if (!$agree) {
            $this->info('The license agreement can be viewed in the included license.txt file.');

            // TODO: use lg_inst_agree, but edit to fit CLI
            if (! $this->confirm('Do you agree to the license agreement? (default: yes)', true)) {
                // "You must accept the license terms to continue"
                $stdErr->writeln('<error>'.lg_inst_must_agree.'</error>');

                return 1;
            }
        }

        /*
         * System Checks
         */

        $this->info("\nChecking System...\n");

        $isWindows = $this->features->isWindows();

        $systemErrors = new MessageBag;

        try {
            $nonEssentialErrors = dispatch_now(new CheckSystem($this->defaultConnection));
        } catch (ValidationException $e) {
            // Get errors MessageBag
            $systemErrors = $e->getErrors();
            $nonEssentialErrors = new MessageBag;
        }

        foreach ($this->systemChecks as $check => $validationMessage) {
            // TODO: Some are allowed, should do a "continue?" message, unless scripted
            if ($systemErrors->has($check)) {
                $stdErr->writeln('<error>'.$check.': '.$validationMessage.'</error>');
            } elseif ($nonEssentialErrors->has($check)) {
                $this->output->writeLn($check.': <info>Missing, but not required</info>');
            } else {
                $this->output->writeLn($check.': <info>OK</info>');
            }
        }

        if (count($systemErrors) > 0) {
            return 1;
        }

        /*
         * Install and Configure HelpSpot
         */
        $this->info("\nBeginning Configuration...\n");

        // Regular Input
        // TODO: Give counts (1/10, 2/10) before descriptions
        $data = [];
        $data['adminname'] = (! $this->option('name') ? $this->ask(lg_inst_yourname.':') : $this->option('name'));
        $data['adminemail'] = (! $this->option('admin-email') ? $this->ask(lg_inst_youremail.':') : $this->option('admin-email'));
        $data['adminpass'] = (! $this->option('password') ? $this->ask(lg_inst_yourpassword.':') : $this->option('password'));
        $data['helpdeskname'] = (! $this->option('company') ? $this->ask(lg_inst_companyhd.':') : $this->option('company'));

        $helper = $this->getHelperSet()->get('question');
        $timezoneQuestion = new ChoiceQuestion(
            lg_inst_tz.' (America/New_York):',
            $this->getTimezones(),
            'America/New_York'
        );
        $data['cHD_TIMEZONE_OVERRIDE'] = (! $this->option('timezone') ? $helper->ask($this->input, $this->output, $timezoneQuestion) : $this->option('timezone'));

        $data['customerid'] = (! $this->option('customer-id') ? $this->ask(lg_inst_custid.': ') : $this->option('customer-id'));
        $data['license'] = (! $this->option('license-file') ? $this->ask(lg_inst_licensefile_cli.': ') : $this->option('license-file'));
        $data['notemail'] = (! $this->option('notification-email') ? $this->ask(lg_inst_notemail.': ') : $this->option('notification-email'));
        $supportType = (! $this->option('support-type') ? $this->ask('Is HelpSpot used for internal support or external? (internal)'.': ', 'internal') : $this->option('support-type'));

        $useSmtp = $this->option('ask-smtp');

        if ($isWindows || $useSmtp) {
            // SMTP Input
            $data['cHD_MAIL_SMTPHOST'] = (! $this->option('smtp-host') ? $this->ask(lg_inst_settings_smtphost.': ') : $this->option('smtp-host'));
            $data['cHD_MAIL_SMTPPORT'] = (! $this->option('smtp-port') ? $this->ask(lg_inst_settings_smtpport.': ') : $this->option('smtp-port'));
            $data['cHD_MAIL_SMTPUSER'] = (! $this->option('smtp-user') ? $this->ask(lg_inst_settings_smtpuser.': ') : $this->option('smtp-user'));
            $data['cHD_MAIL_SMTPPASS'] = (! $this->option('smtp-password') ? $this->ask(lg_inst_settings_smtppass.': ') : $this->option('smtp-password'));
            $data['cHD_MAIL_SMTPPROTOCOL'] = (! $this->option('smtp-protocol') ? $this->ask(lg_inst_settings_smtpprotocol.': ') : $this->option('smtp-protocol'));

            if (strtolower($data['cHD_MAIL_SMTPPROTOCOL']) == 'none') {
                $data['cHD_MAIL_SMTPPROTOCOL'] = null;
            }
        }

        $confErrors = new MessageBag;

        try {
            $data = dispatch_now(new Configure($data, $data['license'], $this->language, $supportType));
        } catch (ValidationException $e) {
            $confErrors = $e->getErrors();
        }

        foreach ($this->validationErrors as $key => $viewString) {
            if ($confErrors->has($key)) {
                $stdErr->writeln('<error>'.$viewString.': '.$confErrors->first($key).'</error>');
            }
        }

        if (count($confErrors) > 0) {
            return 1;
        }

        Artisan::call('install:key');

        $this->info(lg_inst_endtop1);
        $this->info(lg_inst_loc.': '.action('Admin\AdminBaseController@adminFileCalled'));
        $this->info(lg_inst_login1.': '.$data['adminemail']);
        $this->info(lg_inst_login2.': '.$data['adminpass']);

        return 0;
    }

    protected function loadLanguage()
    {
        return (new \language('installer', $this->getLanguage()))
            ->load(['admin.users']);
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
        $this->features = app(\HS\System\Features::class);
        $this->defaultConnection = config('database.default');
    }

    protected function setupTranslations()
    {
        $this->systemChecks = [
            'db'            => strip_tags(lg_inst_checknotdb),
            'mysql'         => strip_tags(lg_inst_checkmysql4),
            'php'           => strip_tags(lg_inst_checknotphp),
            'imap'          => strip_tags(lg_inst_checknotemail),
            'upload'        => strip_tags(lg_inst_uploaddirempty),
            'mbstring'      => strip_tags(lg_inst_checknotmbstring),
            'tidy'          => strip_tags(lg_inst_checknotidy),
            'session'       => strip_tags(lg_inst_checknosessauto),
            'permissions'   => strip_tags(lg_inst_permwarning),
            'datadir'       => strip_tags(lg_inst_datadirnowrite),
        ];

        $this->validationErrors = [
            // 'fname'                 => 'Full Name', # Last name will always trigger this one, if its not present
            'lname'                 => strip_tags(lg_inst_yourname),
            'adminemail'            => strip_tags(lg_inst_youremail),
            'adminpass'             => strip_tags(lg_inst_yourpassword),
            'helpdeskname'          => strip_tags(lg_inst_companyhd),
            'customerid'            => strip_tags(lg_inst_custid),
            'cHD_TIMEZONE_OVERRIDE' => strip_tags(lg_inst_tz),
            'license'               => strip_tags(lg_inst_licensefile_cli),
            'notemail'              => strip_tags(lg_inst_notemail),
            // 'notificationname'      => 'Admin email address', # Produced based on `notemail` being a valid email
        ];
    }

    protected function getTimezones()
    {
        $tz = new Tz;

        return $tz->toArray();
    }

    protected function isInstalled()
    {
        try {
            // Check default connection db exists
            $isInstalled = dispatch_now(new CheckDbExists());
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        return $isInstalled;
    }
}
