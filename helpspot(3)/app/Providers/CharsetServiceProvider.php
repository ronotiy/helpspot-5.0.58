<?php

namespace HS\Providers;

use HS\Charset\Encoder\Manager;
use HS\Charset\Encoder\MbHandler;
use HS\Charset\Detector\MbDetector;
use HS\Charset\Encoder\NullHandler;
use HS\Charset\Encoder\IconvHandler;
use HS\Charset\Detector\NullDetector;
use Illuminate\Support\ServiceProvider;
use HS\Charset\Encoder\Filter\CharacterFilter;
use Illuminate\Contracts\Support\DeferrableProvider;

class CharsetServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public function register()
    {
        $this->app->singleton('charset.detector', function ($app) {
            $systemFeatures = $app['system.features'];

            if ($systemFeatures->hasMb()) {
                return new MbDetector;
            }

            // Fall back to NullDetector if mbstring
            // is not installed
            return new NullDetector;
        });

        $this->app->singleton('charset.encoder', function ($app) {
            $systemFeatures = $app['system.features'];

            $php54orGreater = $systemFeatures->phpAtLeast('5.4.0');
            $hasIconv = $systemFeatures->hasIconv();
            $hasMbstring = $systemFeatures->hasMb();

            $manager = new Manager;

            //$manager->addPreFilter( new CharacterFilter );

            // To Do: Handle if mbstring doesn't support asked for charset
            // See commit 5cd606c92b51f049275cf7762ca86f878281080d
            // https://github.com/UserScape/HelpSpot/commit/5cd606c92b51f049275cf7762ca86f878281080d
            if ($hasIconv && $php54orGreater) {
                $manager->addHandler(new IconvHandler);
            }

            if ($hasMbstring) {
                $manager->addHandler(new MbHandler);
            }

            // Fall back if all else fails
            // Add an error log if this is used?
            $manager->addHandler(new NullHandler);

            return $manager;
        });
    }

    public function provides()
    {
        return ['charset.detector', 'charset.encoder'];
    }
}
