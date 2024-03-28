<?php

namespace HS\Install\System\Checks;

use HS\System\Features;
use HS\Install\System\SystemCheckInterface;

class PhpVersion implements SystemCheckInterface
{
    /**
     * @var \HS\System\Features
     */
    protected $features;

    /**
     * @var bool
     */
    protected $valid;

    /**
     * Php Version Error.
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
     * If we cannot connect to the DB
     * we get an exception here.
     * @return bool
     */
    public function valid()
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }

        $this->valid = $this->features->phpAtLeast('5.6.0');

        if (! $this->valid) {
            $this->error = 'PHP is not at least 5.6.0';
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
