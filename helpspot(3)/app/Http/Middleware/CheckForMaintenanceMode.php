<?php

namespace HS\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\IpUtils;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;

class CheckForMaintenanceMode extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array
     */
    protected $except = [
        'login',
        'password/*',
        'saml2/*',
        'admin/maintenance',
    ];

    /**
     * @inheritDoc
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->isDownForMaintenance() || $this->forcedMaintenanceMode()) {
            $data = json_decode(file_get_contents($this->app->storagePath().'/framework/down'), true);

            if (isset($data['allowed']) && IpUtils::checkIp($request->ip(), (array) $data['allowed'])) {
                return $next($request);
            }

            if ($this->inExceptArray($request)) {
                return $next($request);
            }

            if ($request->is('admin')) {
                // We need to show admins a proper message, and the ability to turn off
                // maintenance mode if they are authenticated
                // See resources/views/errors/503.blade.php
                throw new MaintenanceModeException($data['time'], $data['retry'], $data['message']);
            } elseif ($request->is('widgets')) {
                return response(''); // Empty response for widgets
            } elseif ($request->is('api')) {
                // We'll assume JSON or XML, ignoring the legacy "php" return type
                return ($request->wantsJson())
                    ? response()->json($this->maintenanceResponseJson(), 400)
                    : response($this->maintenanceResponseXml(), 400, ['Content-Type' => 'text/xml']);
            } else /* $request->is('portal') */ {
                // We need to redirect to maintenance page while also allowing the CSS/JS pages to render
                if ($this->portalPageAllowed(request('pg'))) {
                    return $next($request);
                }

                return redirect()->to('index?pg=maintenance');
            }
        }

        return $next($request);
    }

    /**
     * Return a valid Array (JSON) format for maintenance mode API responses
     * @return array[]
     */
    protected function maintenanceResponseJson()
    {
        // Error code/description taken from class.api.base.php
        return ['errors' => [
            'id' => 302,
            'description' => 'Maintenance mode enabled',
        ]];
    }

    /**
     * Return a valid XML format for maintenance mode API responses
     * @return string
     */
    protected function maintenanceResponseXml()
    {
        // Error code/description taken from class.api.base.php
        return <<<RESP
<?xml version="1.0" encoding="UTF-8"?>
<errors>
	<error>
		<id>302</id>
		<description>Maintenance mode enabled</description>
	</error>
</errors>
RESP;
    }


    /**
     * Allow certain portal pages
     * e.g. URI `/index?pg=foo` would check if page "foo" is allowed
     * @param $page
     * @return bool
     */
    protected function portalPageAllowed($page)
    {
        if (in_array($page, ['js', 'maintenance'])) {
            return true;
        }

        if (strpos($page, 'css') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Allow an env var within the .env file to force maintenance mode
     * This assumes Laravel configuration is never cached
     * @return mixed
     */
    protected function forcedMaintenanceMode()
    {
        return config('helpspot.maintenance_mode', false);
    }
}
