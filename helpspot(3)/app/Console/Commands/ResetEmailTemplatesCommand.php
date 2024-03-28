<?php

namespace HS\Console\Commands;

use DB;
use Facades\HS\Cache\Manager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ResetEmailTemplatesCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'reset:email-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset email templates to defaults';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->loadDependencies();
        $this->loadLanguage();

        $stdErr = $this->getOutput()->getErrorStyle();

        if ($this->confirm('Are you want to permanently overwrite existing email templates?', true)) {
            // Email Templates
            $temps = [];
            $temps['portal_reqcreated'] = lg_inst_et_portal_reqcreated;
            $temps['forumreply'] = lg_inst_et_forumreply;
            $temps['staff'] = lg_inst_et_staff;
            $temps['newstaff'] = lg_inst_et_newstaff;
            $temps['ccstaff'] = lg_inst_et_ccstaff;
            $temps['public'] = lg_inst_et_public;
            $temps['reminder'] = lg_inst_et_reminder;
            $temps['external'] = lg_inst_et_external;

            $temps['portal_reqcreated_html'] = lg_inst_et_portal_reqcreated_html;
            $temps['external_html'] = lg_inst_et_external_html;
            $temps['staff_html'] = lg_inst_et_staff_html;
            $temps['ccstaff_html'] = lg_inst_et_ccstaff_html;
            $temps['public_html'] = lg_inst_et_public_html;
            $temps['reminder_html'] = lg_inst_et_reminder_html;
            $temps['forumreply_html'] = lg_inst_et_forumreply_html;
            $temps['newstaff_html'] = lg_inst_et_newstaff_html;
            $temps['sms'] = lg_inst_et_sms;

            $temps['portal_reqcreated_subject'] = lg_inst_et_portal_reqcreated_subject;
            $temps['external_subject'] = lg_inst_et_external_subject;
            $temps['staff_subject'] = lg_inst_et_staff_subject;
            $temps['ccstaff_subject'] = lg_inst_et_ccstaff_subject;
            $temps['public_subject'] = lg_inst_et_public_subject;
            $temps['reminder_subject'] = lg_inst_et_reminder_subject;
            $temps['forumreply_subject'] = lg_inst_et_forumreply_subject;
            $temps['newstaff_subject'] = lg_inst_et_newstaff_subject;

            $temps['partials_replyabove'] = lg_inst_et_partials_replyabove;
            $temps['partials_replyabove_html'] = lg_inst_et_partials_replyabove_html;

            $serializedTemplates = serialize($temps);

            try {
                DB::table('HS_Settings')
                    ->where('sSetting', 'cHD_EMAIL_TEMPLATES')
                    ->update(['tValue' => $serializedTemplates]);
                Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
            } catch (\Exception $e) {
                $stdErr->writeln('<error>'.$e->getMessage().'</error>');

                return 1;
            }

            $this->info('Complete');

            return 0;
        } else {
            $this->info('Cancelled');

            return 0;
        }
    }

    protected function loadLanguage()
    {
        return new \language('installer', 'english-us');
    }

    protected function loadDependencies()
    {
        //
    }
}
