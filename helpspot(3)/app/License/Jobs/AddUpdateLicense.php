<?php

namespace HS\License\Jobs;

use HS\License\CheckLicense;
use Facades\HS\Cache\Manager;

use Illuminate\Bus\Queueable;
use Illuminate\Database\DatabaseManager;

class AddUpdateLicense
{
    use Queueable;

    /**
     * @var string
     */
    private $licenseFilePath;

    /**
     * Create a new job instance.
     *
     * @param $licenseFilePath
     */
    public function __construct($licenseFilePath)
    {
        $this->licenseFilePath = $licenseFilePath;
    }

    /**
     * Execute the job.
     *
     * @param DatabaseManager $db
     * @param CheckLicense $licenseCheck
     * @return \HS\License\License
     * @throws \HS\Base\ValidationException
     */
    public function handle(DatabaseManager $db, CheckLicense $licenseCheck)
    {
        // Validate License
        $customerId = $this->getGlobal($db, 'cHD_CUSTOMER_ID');
        $license = $licenseCheck->getLicense($customerId, $this->licenseFilePath);

        // Update License
        $this->setGlobal($db, 'cHD_LICENSE', $licenseCheck->getRawLicense($this->licenseFilePath));

        return $license;
    }

    protected function getGlobal(DatabaseManager $db, $key)
    {
        $setting = $db->table('HS_Settings')
            ->where('sSetting', $key)
            ->first();

        if ($setting) {
            return $setting->tValue;
        }
    }

    protected function setGlobal(DatabaseManager $db, $key, $value)
    {
        $db->table('HS_Settings')
            ->where('sSetting', $key)
            ->update(['tValue' => $value]);

        Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
    }
}
