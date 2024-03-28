<?php

namespace HS\Install\Updater\Updates\Tasks;

use DB;
use Facades\HS\Cache\Manager;

use HS\Install\Updater\Updates\BaseUpdate;

class EnablePrivateApi extends BaseUpdate
{
    protected $version = '4.0.7';

    /**
     * Enable private API by default so mobile application works
     * without extra configuration.
     */
    public function run()
    {
        DB::table('HS_Settings')->where('sSetting', 'cHD_WSPRIVATE')->update(['tValue' => '1']);
        Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
    }
}
