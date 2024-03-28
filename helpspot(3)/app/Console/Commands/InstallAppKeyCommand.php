<?php

namespace HS\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Console\KeyGenerateCommand;

class InstallAppKeyCommand extends KeyGenerateCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key, if not already set';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $currentKey = trim($this->laravel['config']['app.key']);

        if (strlen($currentKey) == 0) {
            Artisan::call('key:generate', ['--show' => true]);
            $key = trim(Artisan::output());

            $this->writeNewEnvironmentFileWith($key);
        }
    }
}
