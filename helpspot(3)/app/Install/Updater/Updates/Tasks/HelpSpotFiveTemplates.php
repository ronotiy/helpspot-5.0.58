<?php

namespace HS\Install\Updater\Updates\Tasks;

use Illuminate\Support\Facades\Artisan;
use HS\Install\Updater\Updates\BaseUpdate;

class HelpSpotFiveTemplates extends BaseUpdate
{
    protected $version = '5.0.0';

    public function run()
    {
        Artisan::call('mail:convert-templates');
    }
}
