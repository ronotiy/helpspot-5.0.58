<?php


namespace HS\Cloud;


trait IsHosted
{
    protected function isHosted()
    {
        if (config('helpspot.hosted', false)) {
            return true;
        }

        return false;
    }

    protected function storageDisk()
    {
        return $this->isHosted()
            ? 's3'
            : 'local';
    }
}
