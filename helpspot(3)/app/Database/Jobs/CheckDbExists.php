<?php

namespace HS\Database\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\DatabaseManager;

class CheckDbExists
{
    use Queueable;

    /**
     * @var null|string
     */
    private $connectionName;

    /**
     * CheckDbExists constructor.
     * @param $connectionName
     */
    public function __construct($connectionName = null)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DatabaseManager $db)
    {
        $connection = $db->connection($this->connectionName);

        try {
            $version = $connection->table('HS_Settings')
                ->select('tValue')
                ->where('sSetting', 'cHD_VERSION')
                ->first();

            return (bool) $version;
        } catch (\Exception $e) {
            return false;
        }
    }
}
