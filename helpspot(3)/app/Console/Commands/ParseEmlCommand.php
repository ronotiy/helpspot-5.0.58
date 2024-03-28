<?php

namespace HS\Console\Commands;

use HS\IncomingMail\Loggers\DummyLogger;
use HS\IncomingMail\Message;
use HS\IncomingMail\Parsers\MimeDecode;
use Illuminate\Console\Command;
use HS\IncomingMail\Loggers\EchoLogger;
use HS\IncomingMail\Processors\ParserTags;
use HS\IncomingMail\Parsers\MailMimeParser;
use HS\IncomingMail\Processors\Attachments;

class ParseEmlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:parse {--eml=} {--oldparser} {--nogz}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse an eml file for debugging. Pass the --eml= flag with the path to the file. --nogz if it is not encoded.';

    protected $nogz = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eml = $this->option('eml');
        $old = $this->option('oldparser');
        $this->nogz = $this->option('nogz');

        if (! file_exists(base_path($eml))) {
            $this->error('The file '.base_path($eml).' is not found.');

            return;
        }

        $this->loadRequirements();

        $body = $this->load_raw($eml);

        $message = new Message();
        if ($old) {
            $decoder = new MimeDecode($message);
        } else {
            $decoder = new MailMimeParser($message);
        }

        $msg = $decoder->parse($body);
        $body = $msg->getBody();

        $results = [
            ['subject', $msg->getSubject('no subject')],
            ['from name', $msg->getFromName()],
            ['from email', $msg->getFromEmail()],
            ['isForward', $msg->isForward() ? 'true' : 'false'],
            ['isHTML', $msg->isHtml() ? 'true' : 'false'],
            ['isImportant', $msg->isImportant() ? 'true' : 'false'],
        ];
        $this->table(['Title', 'Value'], $results);

        // custom tags
        $tags = new ParserTags($message);

        $results = [
            ['##hs_customer_id', (string) $tags->hs_request_id($body, cHD_EMAILPREFIX)],
            ['##hs_customer_firstname', (string) $tags->hs_customer_firstname()],
            ['##hs_customer_lastname', (string) $tags->hs_customer_lastname()],
            ['##hs_customer_phone', (string) $tags->hs_customer_phone()],
            ['##hs_customer_email', (string) $tags->hs_customer_email()],
            ['##hs_assigned_to', (string) $tags->hs_assigned_to()],
        ];

        $this->comment('+---------------------+---------------------------------------+');
        $this->comment('Parser Tags');
        $this->comment('+---------------------+---------------------------------------+');
        $this->table(['Field', 'Value'], $results);
        $this->comment('+---------------------+---------------------------------------+');
        $this->comment('Body after Parsing');
        $this->comment('+---------------------+---------------------------------------+');
        $this->line($msg->getBody());
        $this->comment('+---------------------+---------------------------------------+');
        $this->comment('Original Text Body');
        $this->comment('+---------------------+---------------------------------------+');
        $this->line($msg->bodyText);
        $this->comment('+---------------------+---------------------------------------+');
        $this->comment('Attachments');
        $this->comment('+---------------------+---------------------------------------+');
        $logger = new DummyLogger($eml);
        $attachments = new Attachments($msg, $logger);
        $msgFiles = $attachments->process();
        if ($msgFiles) {
            $this->line('Name / mimetype');
            foreach ($msgFiles as $file) {
                $this->line($file['name'].' / '.$file['mimetype']);
            }
        }
        $this->comment('+---------------------+---------------------------------------+');
    }

    protected function load_raw($file)
    {
        if ($this->nogz) {
            return file_get_contents(base_path($file));
        }
        return gzdecode(file_get_contents(base_path($file)));
    }

    protected function loadRequirements()
    {
        set_include_path(cBASEPATH.'/helpspot/pear');
        error_reporting(E_ALL ^ E_DEPRECATED);
        require_once base_path('/helpspot/helpspot/lib/api.lib.php');
        require_once base_path('/helpspot/helpspot/lib/util.lib.php');
        require_once base_path('/helpspot/helpspot/lib/utf8.lib.php');
        require_once base_path('/helpspot/helpspot/pear/Mail/helpspot_mimeDecode.php');
        if (! defined('cHD_EMAIL_REPLYABOVE')) {
            define('cHD_EMAIL_REPLYABOVE', '## Reply ABOVE THIS LINE to add a note to this request ##');
        }
        if (! defined('lg_outlookseparator')) {
            define('lg_outlookseparator', '/-----\s*Original Message\s*-----/');
        }
        if (! defined('cHD_STRIPHTML')) {
            define('cHD_STRIPHTML', '2');
        }
        if (! defined('cHD_CUSTOMER_ID')) {
            define('cHD_CUSTOMER_ID', '1');
        }
        if (! defined('cHD_EMAILPREFIX')) {
            define('cHD_EMAILPREFIX', '');
        }
    }
}
