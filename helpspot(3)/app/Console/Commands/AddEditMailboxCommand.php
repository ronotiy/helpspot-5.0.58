<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class AddEditMailboxCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'mailbox
        {--mailbox=INBOX : Mailbox Name}
        {--hostname= : Hostname}
        {--username= : Username}
        {--password= : Password}
        {--port=110 : Port Number}
        {--type= : Connection Protocol (POP/IMAP)}
        {--security= : SSL/TLS}
        {--replyname= : Reply Name}
        {--replyemail= : Reply Email Address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or update an Email Mailbox.';

    /**
     * Add or Edit a Mailbox
     * This will perform an update if the mailbox already exists.
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        // Load Language
        $this->loadLanguage();

        // Get Validator
        $validator = app('validator');

        // Get ability to output to stderr
        $stdErr = $this->getOutput()->getErrorStyle();

        // Boilerplate for HelpSpot internal API
        ob_start();
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        ob_clean();

        /*
         * Check for duplicate mailboxes. The following
         * idential values make up a duplicate mailbox:
         *
         * - sMailbox
         * - sHostname
         * - sUsername
         */
        $existing = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Mailboxes WHERE sMailbox = ? AND sHostname = ? AND sUsername = ?', [
            $this->option('mailbox'),
            $this->option('hostname'),
            $this->option('username'),
        ]);

        $updateMailbox = false;
        $mailboxToUpdate = null;

        if ($existing) {
            $updateMailbox = true;
            $mailboxToUpdate = $existing['xMailbox'];
        }

        $data = $this->inputToArray($updateMailbox, $mailboxToUpdate);

        $validation = $validator->make($data, [
            'sMailbox'      => 'required',
            'sHostname'     => 'required',
            'sUsername'     => 'required',
            'sPassword'     => 'required',
            'sPort'         => 'required',
            'sType'         => 'required',
            'sSecurity'     => 'required',
            'sReplyName'    => 'required',
            'sReplyEmail'   => 'required',
        ]);

        if ($validation->fails()) {
            $messages = $validation->messages()->all();

            foreach ($messages as $key => $message) {
                // Removed unexplained "s" in validation message
                $stdErr->writeln('<error>'.str_replace(' s ', ' ', $message).'</error>');
            }

            return 1;
        }

        $result = apiAddEditMailbox($data, __FILE__, __LINE__);

        // Stop here if all worked
        if (is_numeric($result)) {
            return 0;
        }

        // Else return errors
        if (is_array($result)) {
            foreach ($result as $error) {
                $stdErr->writeln('<error>'.strip_tags($error).'</error>');
            }
        } else {
            $stdErr->writeln('<error>'.strip_tags($result).'</error>');
        }

        return 1;
    }

    protected function inputToArray($update = false, $xMailbox = null)
    {
        $data = [
            'mode' => 'add',
        ];

        if ($update === true) {
            $data['resourceid'] = $xMailbox;
            $data['mode'] = 'edit';
        }

        $data['sMailbox'] = $this->option('mailbox');
        $data['sHostname'] = $this->option('hostname');
        $data['sUsername'] = $this->option('username');
        $data['sPassword'] = $this->option('password');
        $data['sPasswordConfirm'] = $this->option('password'); // Required by apiAddEditMailbox
        $data['sPort'] = $this->option('port');
        $data['sType'] = $this->option('type');
        $data['sSecurity'] = $this->option('security'); // novalidate-cert, tls/novalidate-cert, tls, notls
        $data['sReplyName'] = $this->option('replyname');
        $data['sReplyEmail'] = $this->option('replyemail');

        // We're setting this to 0, all the time
        $data['fAutoResponse'] = 0;

        return $data;
    }

    /**
     * Load language files.
     * @return \language
     */
    protected function loadLanguage()
    {
        return new \language('admin.mailboxes', 'english-us');
    }
}
