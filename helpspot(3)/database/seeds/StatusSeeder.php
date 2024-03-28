<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    public function run()
    {
        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_statusactive, 'fDeleted' => 0, 'fOrder' => 0]);
        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_statusspam, 'fDeleted' => 0, 'fOrder' => 1000000]);

        $activeId = DB::table('HS_luStatus')->select('xStatus')->where('sStatus', lg_inst_statusactive)->first()->xStatus;
        $spamId = DB::table('HS_luStatus')->select('xStatus')->where('sStatus', lg_inst_statusspam)->first()->xStatus;

        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_status1, 'fDeleted' => 0, 'fOrder' => 1]);
        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_status2, 'fDeleted' => 0, 'fOrder' => 2]);
        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_status3, 'fDeleted' => 0, 'fOrder' => 3]);
        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_status4, 'fDeleted' => 0, 'fOrder' => 4]);
        DB::table('HS_luStatus')->insert(['sStatus' => lg_inst_status5, 'fDeleted' => 0, 'fOrder' => 5]);

        /*
         * Record Active and Spam ID's in settings for later usage
         *    since we should not assume the ID's of statuses
         *
         * We assume HS_Settings cHD_STATUS_ACTIVE and cHD_STATUS_SPAM exist
         *    This is important as we can't assume the ID of the database insert
         */
        if ($activeId) {
            DB::table('HS_Settings')->where('sSetting', 'cHD_STATUS_ACTIVE')->update(['tValue' => $activeId]);
        }
        if ($spamId) {
            DB::table('HS_Settings')->where('sSetting', 'cHD_STATUS_SPAM')->update(['tValue' => $spamId]);
        }
    }
}
