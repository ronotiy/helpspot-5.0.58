<?php

namespace HS\Install\Updater\Updates\Tasks;

use Carbon\Carbon;
use HS\AutomationRule;
use Illuminate\Support\Facades\Artisan;
use HS\Install\Updater\Updates\BaseUpdate;

class HelpSpotFiveAutoRules extends BaseUpdate
{
    protected $version = '5.0.0';

    public function run()
    {
        $this->loadDependencies();

        $autoRules = AutomationRule::all();

        foreach($autoRules as $ar) {
            $ar->fDirectOnly = 0;
            $ar->dtNextRun = Carbon::now()->addMinute()->timestamp; // one minute from now

            $def = \hs_unserialize($ar->tRuleDef);

            if($def && $def->option_direct_call_only) {
                $ar->fDirectOnly = 1;
            }

            $ar->save();
        }
    }

    protected function loadDependencies()
    {
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
    }
}
