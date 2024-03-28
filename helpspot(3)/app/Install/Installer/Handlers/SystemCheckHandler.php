<?php

namespace HS\Install\Installer\Handlers;

use HS\System\Features;
use HS\Install\System\Manager;
use HS\Base\ValidationException;
use HS\CommandBus\HandlerInterface;

class SystemCheckHandler implements HandlerInterface
{
    /**
     * @var \HS\System\Features
     */
    private $features;

    /**
     * @var \HS\Install\System\Manager
     */
    private $system;

    public function __construct(Features $features, Manager $system)
    {
        $this->features = $features;
        $this->system = $system;
    }

    public function handle($command)
    {
        $isWindows = $this->features->isWindows();
        $dbConnectionType = $command->dbConnectionType;

        // Don't check mysql just yet
        $except = ['mysql'];

        // If this is not windows
        // we add 'upload' to the exceptions
        // to prevent the check from being run
        if (! $isWindows) {
            $except[] = 'upload';
        }

        // Perform system checks
        $valid = $this->system->checkAllExcept($except);

        // Check mysql exception if this is a MySQL database
        // But don't bother checking if DB already has issues or is invalid already
        if ($dbConnectionType === 'mysql' && $this->system->get('db')->valid() && $valid) {
            $valid = $this->system->get('mysql')->valid();
        }

        if (! $valid) {
            throw new ValidationException($this->system->getErrors());
        }

        return $this->system->getNonEssentialErrors();
    }
}
