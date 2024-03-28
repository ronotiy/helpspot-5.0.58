<?php

namespace HS\Install\System\Checks;

use HS\System\Features;
use HS\Install\System\SystemCheckInterface;

class SystemPermissions implements SystemCheckInterface
{
    /**
     * @var \HS\System\Features
     */
    protected $features;

    /**
     * If system check is valid (OK).
     * @var bool
     */
    protected $valid;

    /**
     * Error.
     * @var string
     */
    protected $error;

    public function __construct(Features $features)
    {
        $this->features = $features;
    }

    /**
     * This is required to pass.
     * @return bool
     */
    public function required()
    {
        return true;
    }

    /**
     * Ensure *.php files aren't 0777 (if linux).
     * @return bool
     */
    public function valid()
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }

        $this->valid = true;

        // We don't want 0777 permissions on Linux
        if (! $this->features->isWindows() && substr(decoct(fileperms(cBASEPATH.'/../public/index.php')), 3) == '777') {
            $this->valid = false;
        }

        if (! $this->valid) {
            $this->error = 'Source Files are Writable';
        }

        return $this->valid;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
}
