<?php

namespace HS\Providers;

use HS\Html\Clean\Cleaner;
use HS\Html\Clean\Decorator\Hotmail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class HtmlServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public function boot()
    {
        $this->app->alias('html.cleaner', \HS\Html\Clean\CleanerInterface::class);
    }

    public function register()
    {
        $this->app->singleton('html.cleaner', function ($app) {
            // Cleaner class and its decorators
            $cleaner = new Cleaner;
            $cleaner = new Hotmail($cleaner);

            return $cleaner;
        });
    }

    public function provides()
    {
        return ['html.cleaner'];
    }
}
