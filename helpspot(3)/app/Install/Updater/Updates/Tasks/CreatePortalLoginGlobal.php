<?php

namespace HS\Install\Updater\Updates\Tasks;

use HS\Install\Updater\Updates\BaseUpdate;

class CreatePortalLoginGlobal extends BaseUpdate
{
    protected $version = '5.0.32';

    public function run()
    {
        $found = $this->db->table('HS_Settings')
            ->where('sSetting', 'cHD_PORTAL_REQUIRE_AUTH')
            ->first();

        if (! $found) {
            $this->db->table('HS_Settings')
                ->insert([
                    'sSetting' => 'cHD_PORTAL_REQUIRE_AUTH',
                    'tValue' => '0',
                ]);
        }
    }
}
