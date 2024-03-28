<?php

namespace HS\Install\Installer\Commands;

class ConfigureCommand
{
    use \HS\Base\Gettable;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $licensePath;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $supportType;

    public function __construct(array $data, $licensePath, $language, $supportType = 'internal')
    {
        $this->data = $data;
        $this->licensePath = $licensePath;
        $this->language = $language;
        $this->supportType = $supportType;
    }
}
