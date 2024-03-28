<?php

namespace HS\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;

class VerifyHelpspotInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $installed = hs_setting('HELPSPOT_INSTALLED', true);

        if (! $installed && Route::is('install') === false) {
            return redirect()->route('install');
        }

        return $next($request);
    }
}
