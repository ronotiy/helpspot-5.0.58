<?php

namespace HS\Console\Commands;

use language;
use HS\Mailbox;
use Illuminate\Console\Command;
use HS\IncomingMail\Mailman\Parse;
use HS\IncomingMail\Mailman\Deliver;
use HS\IncomingMail\Loggers\EchoLogger;
use Illuminate\Support\Facades\Storage;

class ImportEmlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:import {--eml=} {--nogz} {--s3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a known eml file. Pass the --eml= flag with the path. --nogz if it is not encoded.';

    protected $nogz = false;
    private $s3 = false;

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
        $this->loadTheWorld();

        $eml = $this->option('eml');
        $this->nogz = $this->option('nogz');
        $this->s3 = $this->option('s3');

        if ($this->s3) {
            $body = gzdecode(Storage::disk('s3')
                ->get('mail/'.$eml));
        } elseif (file_exists(base_path($eml))) {
            $body = $this->load_raw($eml);
        } else {
            $this->error('The file ' . base_path($eml) . ' is not found.');
            return;
        }

        $parser = new Parse($body);
        $message = $parser->decode();

        // We need a mailbox so grab the first active.
        $mailbox = Mailbox::where('fDeleted', 0)->first();

        $logger = new EchoLogger($eml);
        $deliver = new Deliver($mailbox, $logger);
        $deliver->toDb($message);
    }

    protected function load_raw($file)
    {
        $content = file_get_contents(base_path($file));
        if ($this->nogz) {
            return $content;
        }
        return gzdecode($content);
    }

    /**
     * @param $fileName
     * @return mixed
     */
    protected function fileExists($fileName)
    {
        return Storage::exists('mail/'.$fileName);
    }

    /**
     * Load the world.
     */
    protected function loadTheWorld()
    {
        ob_start();

        /*****************************************
        INCLUDE PATH
         *****************************************/
        set_include_path(cBASEPATH.'/helpspot/pear');

        /*****************************************
        INCLUDE LIBS
         *****************************************/
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        require_once cBASEPATH.'/helpspot/lib/platforms.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/display.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.users.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.notify.php';
        require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';
        require_once cBASEPATH.'/helpspot/lib/class.userscape.bayesian.classifier.php';
        require_once cBASEPATH.'/helpspot/lib/class.mail.rule.php';
        require_once cBASEPATH.'/helpspot/pear/Console/Getopt.php';
        require_once cBASEPATH.'/helpspot/lib/class.business_hours.php';
        require_once cBASEPATH.'/helpspot/lib/class.language.php';
        require_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        require_once cBASEPATH.'/helpspot/lib/class.license.php';
        require_once cBASEPATH.'/helpspot/lib/lookup.lib.php';	//include lookups here so we can use lang abstraction
        $GLOBALS['lang'] = new language('request');
        ob_clean();
    }
}
