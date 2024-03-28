<?php

namespace HS\Install\System\Checks;

use HS\System\Features;
use HS\Install\System\SystemCheckInterface;

class HasTidy implements SystemCheckInterface
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
        return false;
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

        $this->valid = $this->features->hasTidy();

        if (! $this->valid) {
            $this->error = 'PHP Tidy is not available';
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
