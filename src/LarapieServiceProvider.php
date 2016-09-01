<?php

namespace Larapie;

use Illuminate\Support\ServiceProvider;
use Larapie\Config\ConfigNormalizer;
use Larapie\Http\Routing;

class LarapieServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../resources/config/larapie.php' => config_path('larapie.php'),
        ]);

        if (! $this->app->routesAreCached()) {
            $config = $this->app->make('config');
            $larapieConfig = $config->get('larapie');
            if (! $larapieConfig) {
                return;
            }

            $routing = $this->app->make(Routing::class);;
            $normalizedConfig = $routing->registerRoutes($larapieConfig);

            $config->set('larapie', $normalizedConfig);
        }
    }
}
