<?php


namespace HS\Jobs;

/**
 * This trait helps HelpSpot decide:
 * 1. The job category (mail.incoming, mail.private, mail.public)
 * 2. If the job should appear in the UI
 * 3. A human-friendly name for the job
 * Trait AdministratesJobs
 * @package HS\Jobs
 */
trait AdministratesJobs
{
    public function jobName()
    {
        return $this->jobName ?: static::class();
    }

    public function jobCategory()
    {
        return $this->jobCategory ?: 'default';
    }

    /**
     * @return bool
     */
    abstract public function visibleToAdministrators();

    /**
     * @return array
     */
    abstract public function visibleMetaData();
}
