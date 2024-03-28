<?php

namespace HS\Install\Installer\Jobs;

use HS\Jobs\Job;
use HS\System\Features;
use HS\Install\System\Manager;
use HS\Base\ValidationException;

class CheckSystem extends Job
{
    /**
     * @var string
     */
    private $dbConnectionType;

    public function __construct($dbConnectionType)
    {
        $this->dbConnectionType = $dbConnectionType;
    }

    /**
     * @param Features $features
     * @param Manager $system
     * @return mixed
     * @throws ValidationException
     */
    public function handle(Features $features, Manager $system)
    {
        $isWindows = $features->isWindows();
        $dbConnectionType = $this->dbConnectionType;

        // Don't check mysql just yet
        $except = ['mysql'];

        // If this is not windows
        // we add 'upload' to the exceptions
        // to prevent the check from being run
        if (! $isWindows) {
            $except[] = 'upload';
        }

        // Perform system checks
        $valid = $system->checkAllExcept($except);

        // Check mysql exception if this is a MySQL database
        // But don't bother checking if DB already has issues or is invalid already
        if ($dbConnectionType === 'mysql' && $system->get('db')->valid() && $valid) {
            $valid = $system->get('mysql')->valid();
        }

        if (! $valid) {
            throw new ValidationException($system->getErrors());
        }

        return $system->getNonEssentialErrors();
    }
}
