<?php

namespace HS\Console\Commands;

use HS\Mailbox;
use HS\Response;
use Facades\HS\Cache\Manager;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:convert-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert helpspot 4 email template to helpspot 5 templates';

    /**
     * Create a new command instance.
     *
     * @return void
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
        DB::transaction(function () {
            // HS_Settings.cHD_EMAIL_TEMPLATES
            $emailTemplates = DB::table('HS_Settings')
                ->where('sSetting', 'cHD_EMAIL_TEMPLATES')
                ->first();
            $emailTemplates = $this->convertTemplates(hs_unserialize($emailTemplates->tValue));
            DB::table('HS_Settings')
                ->where('sSetting', 'cHD_EMAIL_TEMPLATES')
                ->update(['tValue' => hs_serialize($emailTemplates)]);

            // Mailboxes
            Mailbox::all()->each(function($mailbox) {
                $mailbox->tAutoResponse = $this->convertTemplate($mailbox->tAutoResponse);
                $mailbox->tAutoResponse_html = $this->convertTemplate($mailbox->tAutoResponse_html);
                $mailbox->save();
            });

            // Responses
            Response::all()->each(function($response) {
                $response->tResponse = $this->convertTemplate($response->tResponse);
                $response->save();
            });

            // User signatures
            DB::table('HS_Person')->get()->each(function($personRecord) {
                DB::table('HS_Person')
                    ->where('xPerson', $personRecord->xPerson)
                    ->update([
                        'tSignature' => $this->convertTemplate($personRecord->tSignature),
                        'tSignature_HTML' => $this->convertTemplate($personRecord->tSignature_HTML),
                    ]);
            });

            // Triggers
            include_once cBASEPATH.'/helpspot/lib/class.triggers.php';
            DB::table('HS_Triggers')->get()->each(function($triggerRecord) {
                $trigger = hs_unserialize($triggerRecord->tTriggerDef);
                foreach($trigger->ACTIONS as $key => $action) {
                    foreach($action as $name => $definition) {
                        if(isset($definition['email'])) {
                            $trigger->ACTIONS[$key][$name]['email'] = $this->convertTemplate($definition['email']);
                        }

                        if(isset($definition['subject'])) {
                            $trigger->ACTIONS[$key][$name]['subject'] = $this->convertTemplate($definition['subject']);
                        }
                    }
                }
                DB::table('HS_Triggers')
                    ->where('xTrigger', $triggerRecord->xTrigger)
                    ->update(['tTriggerDef' => hs_serialize($trigger)]);
            });

            // Automation Rules
            include_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
            DB::table('HS_Automation_Rules')->get()->each(function($autoRuleRecord) {
                $autoRule = hs_unserialize($autoRuleRecord->tRuleDef);
                foreach($autoRule->ACTIONS as $key => $action) {
                    foreach($action as $name => $definition) {
                        if(isset($definition['email'])) {
                            $autoRule->ACTIONS[$key][$name]['email'] = $this->convertTemplate($definition['email']);
                        }

                        if(isset($definition['subject'])) {
                            $autoRule->ACTIONS[$key][$name]['subject'] = $this->convertTemplate($definition['subject']);
                        }
                    }
                }
                DB::table('HS_Automation_Rules')
                    ->where('xAutoRule', $autoRuleRecord->xAutoRule)
                    ->update(['tRuleDef' => hs_serialize($autoRule)]);
            });

            // Mail Rules
            include_once cBASEPATH.'/helpspot/lib/class.mail.rule.php';
            DB::table('HS_Mail_Rules')->get()->each(function($mailRuleRecord) {
                $mailRule = hs_unserialize($mailRuleRecord->tRuleDef);
                foreach($mailRule->ACTIONS as $key => $action) {
                    foreach($action as $name => $definition) {
                        if(isset($definition['email'])) {
                            $mailRule->ACTIONS[$key][$name]['email'] = $this->convertTemplate($definition['email']);
                        }

                        if(isset($definition['subject'])) {
                            $mailRule->ACTIONS[$key][$name]['subject'] = $this->convertTemplate($definition['subject']);
                        }
                    }
                }
                DB::table('HS_Mail_Rules')
                    ->where('xMailRule', $mailRuleRecord->xMailRule)
                    ->update(['tRuleDef' => hs_serialize($mailRule)]);
            });

            // Clear caches
            Manager::forgetGroup('users')
                ->forget([
                    Manager::key('CACHE_SETTINGS_KEY'),
                ]);
        });
    }

    /**
     * Convert an array of templates to new new style template variables
     * @param $templates
     * @return mixed
     */
    protected function convertTemplates($templates)
    {
        foreach($templates as $name => $content) {
            $templates[$name] = $this->convertTemplate($content);
        }

        return $templates;
    }

    /**
     * Convert a string into new style template variables
     * @param $content
     * @return string|string[]|null
     */
    protected function convertTemplate($content)
    {
        return preg_replace_callback('/##( *\w+ *)##/i', function($matches) {
            return '{{ $'.strtolower($matches[1]).' }}';
        }, $content);
    }
}
