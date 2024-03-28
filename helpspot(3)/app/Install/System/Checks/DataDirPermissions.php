<?php

namespace HS\Install\System\Checks;

use HS\Install\System\SystemCheckInterface;

class DataDirPermissions implements SystemCheckInterface
{
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

    /**
     * This is required to pass.
     * @return bool
     */
    public function required()
    {
        return true;
    }

    /**
     * Ensure cBASEPATH/data is writeable.
     * @return bool
     */
    public function valid()
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }

        $this->valid = true;
        
        // Data dir and subdirectories need to be writeable
        if (! is_writable(storage_path())
            || ! is_writable(storage_path('app')) || ! is_writable(storage_path('documents'))
            || ! is_writable(storage_path('framework')) || ! is_writable(storage_path('logs'))) {
            $this->valid = false;
        }

        // Double check by actually writing a file
        $result = file_put_contents(storage_path('documents/test-write.txt'), 'testing writable directory');

        if ($result === false) {
            $this->valid = false;
        }

        if (! $this->valid) {
            $this->error = "HelpSpot's 'data' dir is not writable.";
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
