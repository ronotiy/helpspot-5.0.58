<?php

namespace HS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AdministratesJobs;

    protected $jobName = 'Failing Job';
    protected $jobCategory = 'mail.incoming';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        throw new \Exception;
    }

    public function visibleToAdministrators()
    {
        return true;
    }

    public function visibleMetaData()
    {
        return ['foo' => 'bar'];
    }
}
