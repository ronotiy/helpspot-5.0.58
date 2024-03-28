<?php

namespace HS\Install\System\Checks;

use HS\Install\System\SystemCheckInterface;

class UploadWritable implements SystemCheckInterface
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
     * If we cannot connect to the DB
     * we get an exception here.
     * @return bool
     */
    public function valid()
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }

        $this->valid = (ini_get('upload_tmp_dir') != '' && is_writable(ini_get('upload_tmp_dir')));

        if (! $this->valid) {
            $this->error = "PHP's temp upload location is not writable.";
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
