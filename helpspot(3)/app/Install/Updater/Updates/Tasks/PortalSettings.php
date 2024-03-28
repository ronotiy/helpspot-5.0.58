<?php

namespace HS\Install\Updater\Updates\Tasks;

use Facades\HS\Cache\Manager;
use Illuminate\Support\Facades\DB;

use HS\Install\Updater\Updates\BaseUpdate;

class PortalSettings extends BaseUpdate
{
    protected $version = '4.7.2';

    public function run()
    {
        $portalCCC = $this->db->table('HS_Settings')->where('sSetting', 'cHD_PORTAL_ALLOWCC')->first();

        if (! $portalCCC) {
            DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_ALLOWCC', 'tValue' => '0']);
            DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_ALLOWSUBJECT', 'tValue' => '0']);
            Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
        }
    }
}
