<?php

namespace HS\Install\Updater\Updates\Tasks;

use Facades\HS\Cache\Manager;
use Illuminate\Support\Facades\DB;
use HS\Install\Updater\Updates\BaseUpdate;

class MaxRequestHistory extends BaseUpdate
{
    protected $version = '4.8.21';

    public function run()
    {
        $existsCheck = $this->db->table('HS_Settings')->where('sSetting', 'cHD_MAX_REQUEST_HISTORY')->first();

        if (! $existsCheck) {
            DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAX_REQUEST_HISTORY', 'tValue' => '1500']);
            Manager::forget(Manager::CACHE_SETTINGS_KEY);
        }
    }
}
