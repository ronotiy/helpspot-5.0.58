<?php

namespace HS\Console\Commands;

use RuntimeException;
use Illuminate\Foundation\Console\ViewClearCommand;

class MailViewCacheCommand extends ViewClearCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:clear-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all compiled mail view files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->laravel['config']['view.compiled-mail'];

        if (! $path) {
            throw new RuntimeException('View path not found.');
        }

        foreach ($this->files->glob("{$path}/*") as $view) {
            $this->files->delete($view);
        }

        $this->info('Compiled mail views cleared!');
    }
}
