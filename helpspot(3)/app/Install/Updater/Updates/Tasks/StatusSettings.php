<?php

namespace HS\Install\Updater\Updates\Tasks;

use Facades\HS\Cache\Manager;
use Illuminate\Support\Facades\DB;
use HS\Install\Updater\Updates\BaseUpdate;

class StatusSettings extends BaseUpdate
{
    protected $version = '4.0.4';

    /**
     * Add cHD_STATUS_ACTIVE and cHD_STATUS_SPAM settings to HS_Settings table
     * if they are not there.
     *
     * We'll grab the xStatus values of active and spam and update the values there
     */
    public function run()
    {
        if (! $this->hasActiveStatus()) {
            $activeId = DB::table('HS_luStatus')->select('xStatus')->where('sStatus', lg_inst_statusactive)->first();

            if ($activeId) {
                DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STATUS_ACTIVE', 'tValue' => $activeId->xStatus]);
            }
        }

        if (! $this->hasSpamStatus()) {
            $spamId = DB::table('HS_luStatus')->select('xStatus')->where('sStatus', lg_inst_statusspam)->first();

            if ($spamId) {
                DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STATUS_SPAM', 'tValue' => $spamId->xStatus]);
            }
        }

        Manager::forget(Manager::CACHE_SETTINGS_KEY);
    }

    protected function hasActiveStatus()
    {
        return DB::table('HS_Settings')->where('sSetting', 'cHD_STATUS_ACTIVE')->first();
    }

    protected function hasSpamStatus()
    {
        return DB::table('HS_Settings')->where('sSetting', 'cHD_STATUS_SPAM')->first();
    }

    /**
     * Active is the first added status on install
     * SPAM is the second.
     *
     * We grab these two IDs since we can't guarantee any other way will accurately
     * grab the xStatus for Active and SPAM statuses. This will 99% of the time work,
     * which is Good Enough™
     */
    protected function getStatusesByLowestTwoIds()
    {
        $results = DB::table('HS_luStatus')
            ->select('xStatus')
            ->orderBy('xStatus', 'asc')
            ->limit(2)
            ->get();

        // If we have issues, we're back to defaults
        // This is really *just in case* though
        $statusIds = [
            'activeId' => 1,
            'spamId'   => 2,
        ];

        if (! $results || count($results) < 2) {
            return $statusIds;
        }

        // We ordered asc, so this should always be true
        if ($results[0]->xStatus < $results[1]->xStatus) {
            $statusIds = [
                'activeId' => $results[0]->xStatus,
                'spamId'   => $results[1]->xStatus,
            ];
        } elseif ($results[0]->xStatus > $results[1]->xStatus) {
            // But I'm always suspicious.
            // Always.
            $statusIds = [
                'activeId' => $results[1]->xStatus,
                'spamId'   => $results[0]->xStatus,
            ];
        }

        // Implicitly stated here is that if the status ID's are the same,
        // we return the fallback array. But these are databases, and that
        // doesn't happen. I really don't trust computers today.
        return $statusIds;
    }
}
