<?php

namespace HS\Install\Updater\Jobs;

use Illuminate\Bus\Queueable;
use Facades\HS\Cache\Manager as CacheManager;

use HS\Install\Updater\Updates\Manager;
use Illuminate\Database\DatabaseManager;

class RunUpdates
{
    use Queueable;

    /**
     * @var
     */
    private $fromVersion;

    /**
     * @var
     */
    private $toVersion;

    /**
     * RunUpdates constructor.
     * @param $fromVersion
     * @param $toVersion
     */
    public function __construct($fromVersion, $toVersion)
    {
        $this->fromVersion = $fromVersion;
        $this->toVersion = $toVersion;
    }

    /**
     * Execute the job.
     *
     * @param Manager $updater
     * @param DatabaseManager $db
     */
    public function handle(Manager $updater, DatabaseManager $db)
    {
        $updater->runUpdates($this->fromVersion, $this->toVersion);

        // Update Version in Database
        $db->connection()
            ->table('HS_Settings')
            ->where('sSetting', 'cHD_VERSION')
            ->update(['tValue' => instHSVersion]);

        CacheManager::forget(CacheManager::key('CACHE_SETTINGS_KEY'));
    }
}
