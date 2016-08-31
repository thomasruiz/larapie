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
        $config = $this->configNormalizer->normalizeGroupConfig($config);

        $this->router->group($config['group'], function () use (&$config) {
            $config = $this->registerResources($config);
        });

        return $config;
    }

    protected function registerResources($config)
    {
        foreach ($config['resources'] as $resource => &$resourceConfig) {
            $resourceConfig = $this->normalizeConfig($config, $resource, $resourceConfig);
            $this->registerResource($resource, $resourceConfig);
        }

        return $config;
    }

    protected function normalizeConfig($config, $resource, $resourceConfig)
    {
        $this->configNormalizer->checkResourceName($resource, $config['resources']);
        $resourceConfig = $this->configNormalizer->normalizeResourceConfig($resourceConfig);

        return $resourceConfig;
    }

    protected function registerResource($resource, $config)
    {
        if (! $config['disable_routing']) {
            $this->router->resource($resource, Controller::class, $config['router_options']);
        }
    }
}
