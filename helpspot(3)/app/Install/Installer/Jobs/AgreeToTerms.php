<?php

namespace HS\Install\Installer\Jobs;

use HS\Jobs\Job;
use HS\Install\Installer\Actions\AcceptTerms;

class AgreeToTerms extends Job
{
    /**
     * @var bool
     */
    protected $userAgrees;

    public function __construct($userAgrees)
    {
        // Implicitly converts to boolean
        $this->userAgrees = (bool) $userAgrees;
    }

    /**
     * Execute the job.
     *
     * Passes `HS\Base\ValidationException` through
     * @return void
     */
    public function handle()
    {
        return new AcceptTerms($this->userAgrees);
    }
}
