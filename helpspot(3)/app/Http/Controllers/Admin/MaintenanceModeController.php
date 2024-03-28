<?php

namespace HS\Http\Controllers\Admin;

use HS\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MaintenanceModeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:up,down'],
        ]);

        Artisan::call($request->status);

        return redirect()->back();
    }
}
