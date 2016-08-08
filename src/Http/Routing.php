<?php

namespace Larapie\Http;

use Illuminate\Routing\Router;
use Larapie\Config\ConfigNormalizer;

class Routing
{
    private $router;

    private $configNormalizer;

    public function __construct(Router $router, ConfigNormalizer $configNormalizer)
    {
        $this->router = $router;
        $this->configNormalizer = $configNormalizer;
    }

    public function registerRoutes($config)
    {
        foreach ($config['resources'] as $resource => &$resourceConfig) {
            $this->configNormalizer->checkResourceName($resource, $config['resources']);
            $resourceConfig = $this->configNormalizer->normalizeResourceConfig($resourceConfig);
            $this->registerResource($resource, $resourceConfig);
        }

        return $config;
    }

    protected function registerResource($resource, $config)
    {
        if (! $config['disable_routing']) {
            $this->router->resource($resource, Controller::class, $config['router_options']);
        }
    }
}
