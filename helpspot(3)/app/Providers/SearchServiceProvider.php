<?php

namespace HS\Providers;

use HS\Search\Sphinx\Search;
use HS\Search\Sphinx\SphinxQL;
use HS\Search\Sphinx\Connection;
use HS\Search\Sphinx\QueryBuilder;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        // Aliases used for constructor injection
        $this->app->alias('search', \HS\Search\Search::class);
    }

    public function register()
    {
        $this->app->singleton('search', function ($app) {
            $queryBuilder = new QueryBuilder(
                new Connection($app['db']->connection('sphinx'), $app['log']),
                new SphinxQL
            );

            return new Search(
                $queryBuilder,
                $app->make(\HS\Search\Transform::class),
                $app['config']['search.index_prefix']
            );
        });
    }

    public function provides()
    {
        return ['search'];
    }
}
