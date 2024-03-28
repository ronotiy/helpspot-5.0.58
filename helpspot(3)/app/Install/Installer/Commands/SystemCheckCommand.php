<?php

namespace HS\Install\Installer\Commands;

class SystemCheckCommand
{
    use \HS\Base\Gettable;

    /**
     * @var string
     */
    private $dbConnectionType;

    public function __construct($dbConnectionType)
    {
        $this->dbConnectionType = $dbConnectionType;
    }
}
