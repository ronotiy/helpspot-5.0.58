<?php

namespace HS\Providers;

use HS\System\Features;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class SystemServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public function boot()
    {
        $this->app->alias('system.features', \HS\System\Features::class);
    }

    public function register()
    {
        $this->app->singleton('system.features', function ($app) {
            return new Features;
        });
    }

    public function provides()
    {
        return ['system.features'];
    }
}
