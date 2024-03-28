<?php

namespace HS\Providers;

use Illuminate\Support\ServiceProvider;

class ErrorsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton('form.errors', \HS\Html\FormErrors::class);
    }
}
