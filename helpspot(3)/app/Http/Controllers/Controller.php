<?php

namespace HS\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function loadSettings()
    {
        DB::table('HS_Settings')->get()->each(function ($setting) {
            if (! defined($setting->sSetting)) {
                define($setting->sSetting, $setting->tValue);
            }
        });
    }
}
