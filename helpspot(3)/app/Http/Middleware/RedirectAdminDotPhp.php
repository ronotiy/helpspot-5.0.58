<?php

namespace HS\Http\Middleware;

use Closure;

class RedirectAdminDotPhp
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
        // GET requests to admin.php can be redirected
        // but we should be sure to "relay" flash session data
        if (strpos($request->fullUrl(), '/admin.php') !== false && $request->isMethod('get')) {
            $request->session()->reflash();
            return redirect($this->cleanAdminUrl($request->fullUrl()));
        }

        return $next($request);
    }

    protected function cleanAdminUrl($redirectTo)
    {
        return with(str_replace('/admin.php', '/admin', $redirectTo), function($redirectTo) {
            return str_replace('/admin/admin', '/admin', $redirectTo);
        });
    }
}
