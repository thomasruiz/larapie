<?php

namespace Larapi\Http;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Collection;
use Larapi\Contracts\TransformableContract;

class Controller extends BaseController
{
    private $request;

    private $config;

    private $responseFactory;

    private $parents;

    private $model;

    private $resourceName;

    public function __construct(Request $request, Repository $config, ResponseFactory $responseFactory)
    {
        $this->request = $request;
        $this->config = $config->get('larapi');
        $this->responseFactory = $responseFactory;

        if ($request->route()) {
            $this->resolveModelAndParents();
        }
    }

    public function index()
    {
        $collection = $this->findCollection();

        return $this->respond($collection, 200);
    }

    public function show()
    {
        $model = $this->findModelInHierarchy();

        if ($model === null) {
            return $this->notFound();
        }

        return $this->respond($model, 200);
    }

    public function store()
    {
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
        $model = $this->findModelInHierarchy();

        if ($model === null) {
            return $this->notFound();
        }

        $model->update($this->request->all());

        return $this->respond($model, 200);
    }

    public function destroy()
    {
        $model = $this->findModelInHierarchy();

        if (! $model) {
            return $this->notFound();
        }

        $model->delete();

        return $this->responseFactory->json(null, 204);
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
        array_pop($routeParts);

        $this->resourceName = $this->resolveResourceName($routeParts);
        $this->model = $this->resolveModelFromResourceName(implode('.', $routeParts));

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
        return $routeParts[ count($routeParts) - 1 ];
    }

    protected function resolveModelFromResourceName($resourceName)
    {
        return $this->config['resources'][ $resourceName ]['model'];
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
        return call_user_func([$model, 'find'], $this->request->route($resourceName));
    }

    protected function notFound()
    {
        return $this->responseFactory->json(null, 404);
    }

    protected function respond($model, $code)
    {
        $transformed = $this->applyTransformer($model);

        return $this->responseFactory->json($transformed, $code);
    }
}
