<?php

namespace Larapie\Http;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;

class RequestResolver
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Larapie\Http\ModelResource
     */
    private $resource;

    /**
     * RequestResolver constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Container $container, Repository $config)
    {
        $this->container = $container;
        $this->config = $config->get('larapie');
    }

    /**
     * @return \Illuminate\Http\Request
     */
    public function resolve()
    {
        $request = $this->container->make('request');

        $routeParts = $this->extractRouteParts($request);
        $action = $this->extractRouteAction($routeParts);
        $this->removeRoutePartsNoise($routeParts);

        $configName = implode('.', $routeParts);
        $this->resource = $this->resolveResource($configName, $routeParts);
        $this->request = $this->resolveRequestFromConfig($configName, $action, $request);

        return $this->request;
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \string[]
     */
    protected function extractRouteParts(Request $request)
    {
        return explode('.', $request->route()->getName());
    }

    /**
     * @param string[] $routeParts
     *
     * @return mixed
     */
    protected function extractRouteAction(&$routeParts)
    {
        return array_pop($routeParts);
    }

    /**
     * @param $routeParts
     */
    protected function removeRoutePartsNoise(&$routeParts)
    {
        if (isset($this->config['group']['as'])) {
            array_shift($routeParts);
        }
    }

    /**
     * @param string $configName
     * @param        $routeParts
     *
     * @return \Larapie\Http\ModelResource
     */
    protected function resolveResource($configName, $routeParts)
    {
        $name = $this->resolveResourceName($routeParts);
        $model = $this->resolveModelFromConfig($configName);
        $parents = $this->resolveParents($routeParts);
        $authorization = $this->resolveAuthorizationFromConfig($configName);
        $resource = new ModelResource($parents, $model, $name, $authorization);

        return $resource;
    }

    /**
     * @param string[] $routeParts
     *
     * @return string mixed
     */
    protected function resolveResourceName($routeParts)
    {
        return $routeParts[count($routeParts) - 1];
    }

    /**
     * @param string $resourceName
     *
     * @return string
     */
    protected function resolveModelFromConfig($resourceName)
    {
        return $this->config['resources'][$resourceName]['model'];
    }

    /**
     * @param string $resourceName
     *
     * @return bool
     */
    protected function resolveAuthorizationFromConfig($resourceName)
    {
        $authorization = isset($this->config['group']['authorization']) && $this->config['group']['authorization'];
        if (isset($this->config['resources'][$resourceName]['authorization'])) {
            $authorization = $this->config['resources'][$resourceName]['authorization'];
        }

        return $authorization;
    }

    /**
     * @param string[] $routeParts
     *
     * @return array
     */
    protected function resolveParents($routeParts)
    {
        array_pop($routeParts);

        return count($routeParts) ? $routeParts : [];
    }

    /**
     * @param string $resourceName
     * @param string $route
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Request
     */
    protected function resolveRequestFromConfig($resourceName, $route, Request $request)
    {
        $resourceConfig = $this->config['resources'][$resourceName];

        if (in_array($route, ['store', 'update'])) {
            if ($this->hasCustomRequestsForRoute($route, $resourceConfig)) {
                $request = $this->container->make($resourceConfig['requests'][$route]);
            } elseif ($this->hasCustomRequestForResource($resourceConfig)) {
                $request = $this->container->make($resourceConfig['request']);
            }
        }

        if ($request instanceof ValidatesWhenResolved) {
            $request->validate();
        }

        return $request;
    }

    /**
     * @param string $route
     * @param array $resourceConfig
     *
     * @return bool
     */
    protected function hasCustomRequestsForRoute($route, $resourceConfig)
    {
        return isset($resourceConfig['requests']) && isset($resourceConfig['requests'][$route]);
    }

    /**
     * @param array $resourceConfig
     *
     * @return bool
     */
    protected function hasCustomRequestForResource($resourceConfig)
    {
        return isset($resourceConfig['request']);
    }

    /**
     * @return \Larapie\Http\ModelResource
     */
    public function getResource()
    {
        return $this->resource;
    }
}
