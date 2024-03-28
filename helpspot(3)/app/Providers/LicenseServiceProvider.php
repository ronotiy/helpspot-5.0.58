<?php

namespace HS\Providers;

use usLicense;
use HS\License\Decryptor;
use HS\License\CheckLicense;
use HS\License\Subscription;
use Illuminate\Support\ServiceProvider;

class LicenseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! defined('SSKEY')) {
            define('SSKEY', md5('wookie fulton $2Zgh91'));
        }

        $this->app->alias('license', \HS\License\Decryptor::class);
        $this->app->alias('license.check', \HS\License\CheckLicense::class);

        $this->app->singleton(\HS\License\Subscription::class, function () {
            $licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
            $license = $licenseObj->getLicense();

            return new Subscription($license);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (! defined('SSKEY')) {
            define('SSKEY', md5('wookie fulton $2Zgh91'));
        }

        $this->app->singleton('license', function ($app) {
            return new Decryptor($app->make('hs.encrypter'), hs_setting('SSKEY'));
        });

        $this->app->singleton('license.check', function ($app) {
            return new CheckLicense($app['license'], $app['files']);
        });
    }
}
