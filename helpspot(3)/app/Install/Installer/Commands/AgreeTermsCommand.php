<?php

namespace HS\Install\Installer\Commands;

class AgreeTermsCommand
{
    use \HS\Base\Gettable;

    /**
     * @var bool
     */
    protected $userAgrees;

    public function __construct($userAgrees)
    {
        // Implicitly converts to boolean
        $this->userAgrees = (bool) $userAgrees;
    }
}
