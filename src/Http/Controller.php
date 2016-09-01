<?php

namespace Larapie\Http;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    private $config;

    private $responseFactory;

    private $requestResolver;

    /**
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var \Larapie\Http\ModelResource
     */
    private $resource;

    public function __construct(Repository $config, ResponseFactory $responseFactory, RequestResolver $requestResolver)
    {
        $this->config = $config->get('larapie');
        $this->responseFactory = $responseFactory;
        $this->requestResolver = $requestResolver;
    }

    public function index()
    {
        $this->request = $this->requestResolver->resolve();
        $this->resource = $this->requestResolver->getResource();

        $collection = $this->findCollection();

        return $this->responseFactory->respond($collection, 200);
    }

    public function show()
    {
        $this->request = $this->requestResolver->resolve();
        $this->resource = $this->requestResolver->getResource();

        $model = $this->findModelInHierarchy();

        if ($model === null) {
            return $this->notFound();
        }

        return $this->responseFactory->respond($model, 200);
    }

    public function store()
    {
        $this->request = $this->requestResolver->resolve();
        $this->resource = $this->requestResolver->getResource();

        if (count($this->resource->getParents())) {
            $parent = $this->findLastParent();

            if ($parent === null) {
                return $this->notFound();
            }

            $model = $parent->{str_plural($this->resource->getName())}()->create($this->request->all());
        } else {
            $model = call_user_func([$this->resource->getModel(), 'create'], $this->request->all());
        }

        return $this->responseFactory->respond($model, 201);
    }

    public function update()
    {
        $this->request = $this->requestResolver->resolve();
        $this->resource = $this->requestResolver->getResource();

        $model = $this->findModelInHierarchy();

        if ($model === null) {
            return $this->notFound();
        }

        $model->update($this->request->all());

        return $this->responseFactory->respond($model, 200);
    }

    public function destroy()
    {
        $this->request = $this->requestResolver->resolve();
        $this->resource = $this->requestResolver->getResource();

        $model = $this->findModelInHierarchy();

        if (! $model) {
            return $this->notFound();
        }

        $model->delete();

        return $this->responseFactory->respond(null, 204);
    }

    protected function findCollection()
    {
        if (count($this->resource->getParents()) > 0) {
            $lastParent = $this->findLastParent();
            $collection = $lastParent->{str_plural($this->resource->getName())};

            return $collection;
        } else {
            $collection = call_user_func([$this->resource->getModel(), 'all']);

            return $collection;
        }
    }

    protected function findLastParent()
    {
        $parents = $this->resource->getParents();

        $parent = array_shift($parents);
        $lastParent = $this->findModel($this->config['resources'][ $parent ]['model'], $parent);
        foreach ($parents as $parent) {
            $lastParent = $lastParent->{str_plural($parent)}()->find($this->request->route($parent));
        }

        return $lastParent;
    }

    protected function findModelInHierarchy()
    {
        if (count($this->resource->getParents())) {
            $parent = $this->findLastParent();

            if ($parent === null) {
                return null;
            }

            $caller = $parent->{str_plural($this->resource->getName())}();
        } else {
            $caller = $this->resource->getModel();
        }

        return $this->findModel($caller, $this->resource->getName());
    }

    protected function findModel($model, $resourceName)
    {
        $id = $this->findIdInRoute($resourceName);

        return call_user_func([$model, 'find'], $id);
    }

    protected function notFound()
    {
        return $this->responseFactory->respond(['error' => 'Not Found'], 404);
    }

    protected function findIdInRoute($resourceName)
    {
        $id = $this->request->route($resourceName)
            ?: $this->request->route(str_plural($resourceName))
                ?: $this->request->route(str_singular($resourceName));

        return $id;
    }
}
