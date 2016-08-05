<?php

namespace Larapie\Http;

use Illuminate\Routing\Router;
use Larapie\LarapieException;

class Routing
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function registerRoutes($config)
    {
        foreach ($config['resources'] as $resourceName => &$resourceConfig) {
            $this->checkResourceName($resourceName, $config['resources']);
            $resourceConfig = $this->formatConfig($resourceConfig);

            if (! $resourceConfig['disable_routing']) {
                $this->registerResource($resourceName, $resourceConfig);
            }
        }

        unset($resourceConfig);

        return $config;
    }

    protected function formatConfig($config)
    {
        if (is_string($config)) {
            $config = ['model' => $config];
        } elseif (! isset($config['model'])) {
            throw new LarapieException('Unable to register the resource: model missing.');
        }

        $config = $this->removeDisabledOptions($config);

        $defaults = [
            'router_options'  => [],
            'disable_routing' => false,
        ];

        return $config + $defaults;
    }

    protected function registerResource($name, $config)
    {
        $this->router->resource($name, Controller::class, $config['router_options']);
    }

    protected function removeDisabledOptions($config)
    {
        $config = $this->clearRouterOptionNames($config);
        $config = $this->validateRouterOptionOnly($config);
        $config = $this->validateRouterOptionExcept($config);

        return $config;
    }

    protected function checkResourceName($resourceName, $config)
    {
        $resources = explode('.', $resourceName);
        array_pop($resources);

        foreach ($resources as $resource) {
            if (! isset($config[ $resource ])) {
                throw new LarapieException(
                    "Unable to register nested resource: unknown parent `$resource`. You can " .
                    "add it to the configuration file with the option `disable_routing` set " .
                    "to true if you don't need the routes."
                );
            }
        }
    }

    protected function clearRouterOptionNames($config)
    {
        if (isset($config['router_options']['names'])) {
            unset($config['router_options']['names']);
        }

        return $config;
    }

    protected function validateRouterOptionOnly($config)
    {
        if (isset($config['router_options']['only'])) {
            $config['router_options']['only'] = $this->ensureArrayValue($config['router_options']['only']);

            $finalOnlyConfig = $this->clearInvalidOnlyOptions($config);

            if (count($finalOnlyConfig) === 0) {
                $config['disable_routing'] = true;
            } else {
                $config['router_options']['only'] = $finalOnlyConfig;
            }
        }

        return $config;
    }

    protected function clearInvalidOnlyOptions($config)
    {
        $result = [];
        foreach ($config['router_options']['only'] as $item) {
            if (! in_array($item, ['create', 'edit'])) {
                $result[] = $item;
            }
        }

        return $result;
    }

    protected function validateRouterOptionExcept($config)
    {
        $config['router_options']['except'] = empty($config['router_options']['except'])
            ? [] : $this->ensureArrayValue($config['router_options']['except']);

        $config['router_options']['except'] += ['create', 'edit'];

        return $config;
    }

    protected function ensureArrayValue($value)
    {
        if (is_string($value)) {
            return [$value];
        }

        return $value;
    }
}
