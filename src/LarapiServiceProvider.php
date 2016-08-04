<?php

namespace Larapi;

use Illuminate\Support\ServiceProvider;
use Larapi\Http\Routing;

class LarapiServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        if (! $this->app->routesAreCached()) {
            $config = $this->app->make('config');

            $routing = new Routing($this->app->make('router'));
            $normalizedConfig = $routing->registerRoutes($config->get('larapi'));

            $config->set('larapi', $normalizedConfig);
        }
    }
}
