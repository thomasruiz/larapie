<?php

namespace Larapie\Http;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use Larapie\Contracts\TransformableContract;

class Controller extends BaseController
{
    /** @var Request */
    private $request;

    private $config;

    private $container;

    private $responseFactory;

    private $parents;

    private $model;

    private $resourceName;

    public function __construct(Container $container, Repository $config, ResponseFactory $responseFactory)
    {
        $this->config = $config->get('larapie');
        $this->container = $container;
        $this->responseFactory = $responseFactory;
    }

    public function index()
    {
        $this->resolveRequest();

        $collection = $this->findCollection();

        return $this->respond($collection, 200);
    }

    public function show()
    {
        $this->resolveRequest();

        $model = $this->findModelInHierarchy();

        if ($model === null) {
            return $this->notFound();
        }

        return $this->respond($model, 200);
    }

    public function store()
    {
        $this->resolveRequest();

        if (count($this->parents)) {
            $parent = $this->findLastParent();

            if ($parent === null) {
                return $this->notFound();
            }

            $model = $parent->{str_plural($this->resourceName)}()->create($this->request->all());
        } else {
            $model = call_user_func([$this->model, 'create'], $this->request->all());
        }

        return $this->respond($model, 201);
    }

    public function update()
    {
        $this->resolveRequest();

        $model = $this->findModelInHierarchy();

        if ($model === null) {
            return $this->notFound();
        }

        $model->update($this->request->all());

        return $this->respond($model, 200);
    }

    public function destroy()
    {
        $this->resolveRequest();

        $model = $this->findModelInHierarchy();

        if (! $model) {
            return $this->notFound();
        }

        $model->delete();

        return $this->responseFactory->json(null, 204);
    }

    protected function applyTransformer($transformable)
    {
        if ($transformable instanceof Collection) {
            return $transformable->map(function ($model) {
                return $this->applyTransformer($model);
            });
        }

        if ($transformable instanceof TransformableContract) {
            $transformer = $transformable->getTransformerClass();

            return $transformer->transform($transformable);
        }

        return $transformable;
    }

    protected function findCollection()
    {
        if (count($this->parents) > 0) {
            $lastParent = $this->findLastParent();
            $collection = $lastParent->{str_plural($this->resourceName)};

            return $collection;
        } else {
            $collection = call_user_func([$this->model, 'all']);

            return $collection;
        }
    }

    protected function findLastParent()
    {
        $parent = array_shift($this->parents);
        $lastParent = $this->findModel($this->config['resources'][ $parent ]['model'], $parent);
        foreach ($this->parents as $parent) {
            $lastParent = $lastParent->{str_plural($parent)}()->find($this->request->route($parent));
        }

        return $lastParent;
    }

    protected function findModelInHierarchy()
    {
        if (count($this->parents)) {
            $parent = $this->findLastParent();

            if ($parent === null) {
                return null;
            }

            $caller = $parent->{str_plural($this->resourceName)}();
        } else {
            $caller = $this->model;
        }

        return $this->findModel($caller, $this->resourceName);
    }

    protected function findModel($model, $resourceName)
    {
        $id = $this->findIdInRoute($resourceName);

        return call_user_func([$model, 'find'], $id);
    }

    protected function notFound()
    {
        return $this->responseFactory->json(['error' => 'Not Found'], 404);
    }

    protected function respond($model, $code)
    {
        $transformed = $this->applyTransformer($model);

        return $this->responseFactory->json($transformed, $code);
    }

    private function resolveRequest()
    {
        $this->request = $this->container->make('request');

        if ($this->request->route()) {
            $this->resolveModelAndParents();
        }
    }

    protected function resolveModelAndParents()
    {
        $routeParts = $this->resolveModel();
        $this->resolveParents($routeParts);
    }

    protected function resolveModel()
    {
        $routeParts = $this->extractRouteParts();

        // Removing "index", "show",...
        $route = array_pop($routeParts);

        if (isset($this->config['group']['as'])) {
            array_shift($routeParts);
        }

        if (isset($this->config['group']['prefix'])) {
            array_shift($routeParts);
        }

        $indexInConfig = implode('.', $routeParts);
        $this->resolveResourceName($routeParts);
        $this->resolveModelFromConfig($indexInConfig);
        $this->resolveRequestFromConfig($indexInConfig, $route);

        return $routeParts;
    }

    protected function resolveParents($routeParts)
    {
        array_pop($routeParts);

        if (count($routeParts)) {
            $this->parents = $routeParts;
        }
    }

    protected function extractRouteParts()
    {
        return explode('.', $this->request->route()->getName());
    }

    protected function resolveResourceName($routeParts)
    {
        $this->resourceName = $routeParts[ count($routeParts) - 1 ];
    }

    protected function resolveModelFromConfig($resourceName)
    {
        $this->model = $this->config['resources'][ $resourceName ]['model'];
    }

    protected function resolveRequestFromConfig($resourceName, $route)
    {
        $resourceConfig = $this->config['resources'][ $resourceName ];

        if (in_array($route, ['store', 'update'])) {
            if (isset($resourceConfig['requests']) && isset($resourceConfig['requests'][ $route ])) {
                $this->request = $this->container->make($resourceConfig['requests'][ $route ]);
            } elseif (isset($resourceConfig['request'])) {
                $this->request = $this->container->make($resourceConfig['request']);
            }

            if ($this->request instanceof ValidatesWhenResolved) {
                $this->request->validate();
            }
        }
    }

    protected function findIdInRoute($resourceName)
    {
        $id = $this->request->route($resourceName)
            ?: $this->request->route(str_plural($resourceName))
            ?: $this->request->route(str_singular($resourceName));

        return $id;
    }
}
