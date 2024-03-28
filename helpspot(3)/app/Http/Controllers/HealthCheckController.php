<?php

namespace HS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @link https://stackoverflow.com/questions/3668506/efficient-sql-test-query-or-validation-query-that-will-work-across-all-or-most
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Most efficient/cross-compatible database connection/query check
        DB::select('SELECT 1');

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'key' => 'loginform'], 200);
        }

        return response('<div id="loginform">ok</div>', 200);
    }
}
