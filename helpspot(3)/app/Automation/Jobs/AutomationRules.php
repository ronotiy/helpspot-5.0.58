<?php

namespace HS\Automation\Jobs;

use HS\AutomationRule;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Run Automation Rules
 * Class AutomationRules.
 */
class AutomationRules
{
    use Queueable;

    /**
     * @var array
     */
    private $rule_ids;

    /**
     * Create a new job instance.
     *
     * @param array $rule_ids
     */
    public function __construct($rule_ids = [])
    {
        $this->rule_ids = $rule_ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->load();

        /*****************************************
         * GET LICENSE
         *****************************************/
        $licenseObj = new \usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
        $GLOBALS['license'] = $licenseObj->getLicense();

        /*****************************************
        SET VARS
        *****************************************/
        $page = '';

        /*****************************************
        INCLUDE LANGUAGE
        *****************************************/
        $GLOBALS['lang'] = new \language($page);
        ob_start();
        require_once cBASEPATH.'/helpspot/lib/lookup.lib.php';	//include lookups here so we can use lang abstraction
        ob_clean();

        /**
         * AUTOMATION RULES
         * 1. If we specify rule IDs, we'll only run those rule ID's
         * 2. If the rule has flag fDirectOnly == 1, we only run it if the ID is included in $this->>rule_ids
         * 3. All other rules are ran by their schedule via dtNextRun
         */
        if (count($this->rule_ids) > 0) {
            $autoRules = AutomationRule::active() // Not deleted
                ->whereIn('xAutoRule', $this->rule_ids) // Is in given rule_ids
                // We ignore schedules when calling by ID's
                // It doesn't make sense to enforce "direct only" when calling by ID's
                ->get();
        } else {
            $autoRules = AutomationRule::active() // Not deleted
                ->schedulable() // Not "direct only"
                ->pending() // Next run date is due
                ->get();
        }

        echo 'found rules '. $autoRules->count()."\n";
        foreach ($autoRules as $ar) {
            if ($ar->isScheduled()) {
                // Set next schedule date *before* we run the rule to prevent
                // any fatal errors from causing the rule to be run too soon
                // on the next call to `artisan schedule:run`
                $ar->setNextRunTime();
            }

            $rule = hs_unserialize($ar->tRuleDef);
            if ($rule instanceof \hs_auto_rule) {
                $rule->id = $ar->xAutoRule; // Add the rule id so we can use it to find out how many times it's ran.
                $rule->ApplyRule(true);
            }
        }
    }

    protected function load()
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
        require_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
        require_once cBASEPATH.'/helpspot/pear/Console/Getopt.php';
        require_once cBASEPATH.'/helpspot/lib/class.license.php';

        require_once cBASEPATH.'/helpspot/lib/class.filter.php';
        require_once cBASEPATH.'/helpspot/lib/class.language.php';
        require_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        ob_clean();
    }
}
