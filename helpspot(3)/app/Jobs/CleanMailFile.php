<?php

namespace HS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CleanMailFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AdministratesJobs;

    protected $jobName = 'Delete Raw Email File';
    protected $jobCategory = 'mail.incoming';

    /**
     * @var string
     */
    private $fileName;

    /**
     * Create a new job instance.
     *
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Storage::disk('local')
            ->delete('mail/'.$this->fileName);
    }

    public function visibleToAdministrators()
    {
        return true;
    }

    public function visibleMetaData()
    {
        return [
            'filename' => 'mail/'.$this->fileName,
        ];
    }
}
