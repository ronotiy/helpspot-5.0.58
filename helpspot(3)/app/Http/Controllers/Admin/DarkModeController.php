<?php

namespace HS\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use HS\Http\Controllers\Controller;

class DarkModeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        DB::table('HS_Person')
            ->where('xPerson', auth()->user()->xPerson)
            ->update(['fDarkMode' => (inDarkMode() ? 0 : 1)]);

        return back();
    }
}
