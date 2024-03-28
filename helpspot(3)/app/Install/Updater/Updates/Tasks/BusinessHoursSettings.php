<?php

namespace HS\Install\Updater\Updates\Tasks;

use Facades\HS\Cache\Manager;

use HS\Install\Updater\Updates\BaseUpdate;

class BusinessHoursSettings extends BaseUpdate
{
    protected $version = '4.5.0';

    public function run()
    {
        $bizHours = $this->db->table('HS_Settings')->where('sSetting', 'cHD_BUSINESS_HOURS')->first();
        $hours = unserialize($bizHours->tValue);

        // If we can't unserialize then we have bad data
        // update this with the default 9:00 AM to 5:00 PM
        if (! $hours) {
            $this->db->table('HS_Settings')
                ->where('sSetting', 'cHD_BUSINESS_HOURS')
                ->update(['tValue' => 'a:2:{s:8:"bizhours";a:7:{i:0;b:0;i:1;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:2;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:3;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:4;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:5;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:6;b:0;}s:8:"holidays";a:0:{}}']);
            Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
        }
    }
}
