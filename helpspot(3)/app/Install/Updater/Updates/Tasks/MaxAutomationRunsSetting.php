<?php

namespace HS\Install\Updater\Updates\Tasks;

use HS\Cache\Manager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use HS\Install\Updater\Updates\BaseUpdate;

class MaxAutomationRunsSetting extends BaseUpdate
{
    protected $version = '4.8.23';

    public function run()
    {
        $existsCheck = $this->db->table('HS_Settings')->where('sSetting', 'cHD_MAX_AUTO_RUNS')->first();

        if(! $existsCheck) {
            DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAX_AUTO_RUNS', 'tValue' => '50']);
        } else {
            DB::table('HS_Settings')->where('sSetting', 'cHD_MAX_AUTO_RUNS')->update(['tValue' => '50']);
        }

        Cache::forget(Manager::CACHE_SETTINGS_KEY);
    }
}
