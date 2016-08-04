<?php

namespace Larapie;

use Illuminate\Support\ServiceProvider;
use Larapie\Http\Routing;

class LarapieServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        if (! $this->app->routesAreCached()) {
            $config = $this->app->make('config');

            $routing = new Routing($this->app->make('router'));
            $normalizedConfig = $routing->registerRoutes($config->get('larapie'));

            $config->set('larapie', $normalizedConfig);
        }
    }
}
