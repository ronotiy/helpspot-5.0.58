<?php

namespace HS\Console\Commands;

use Illuminate\Console\Command;
use HS\Attachments\Jobs\SaveAttachmentsToDisk;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AttachmentsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'attachments:tofile {--path= : Path to attachment directory.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save database attachments to the File system';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $path = (!$this->option('path')) ? $this->ask('Attachment Path (Default: '.storage_path('documents').'):', storage_path('documents')) : $this->option('path');

        try {
            dispatch(new SaveAttachmentsToDisk($path));
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }
        }

        if (config('app.debug') && function_exists('xdebug_peak_memory_usage')) {
            $this->info("\nMemory Usage: ".round(xdebug_peak_memory_usage() / 1048576, 2).'MB');
        }

        $this->info('Attachments successfully moved from the database to the file system.');
    }
}
