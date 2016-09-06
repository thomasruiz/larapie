<?php

namespace Larapie\Http;

use Illuminate\Contracts\Routing\ResponseFactory as LaravelResponseFactory;
use Illuminate\Support\Collection;
use Larapie\Contracts\DirectTransformableContract;
use Larapie\Contracts\TransformableContract;

class ResponseFactory
{
    /**
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    private $responseFactory;

    /**
     * ResponseFactory constructor.
     *
     * @param \Illuminate\Contracts\Routing\ResponseFactory $responseFactory
     */
    public function __construct(LaravelResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param \Larapie\Contracts\TransformableContract|\Illuminate\Support\Collection|array|null $content
     * @param int                                                                                $status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond($content, $status = 200)
    {
        $transformed = $this->transform($content);

        return $this->responseFactory->json($transformed, $status);
    }

    /**
     * @param \Larapie\Contracts\TransformableContract|\Illuminate\Support\Collection|null $transformable
     *
     * @return array|mixed
     */
    protected function transform($transformable)
    {
        if ($transformable instanceof Collection) {
            return $this->transformCollection($transformable);
        }

        if ($transformable instanceof TransformableContract) {
            return $this->transformTransformable($transformable);
        }

        if ($transformable instanceof DirectTransformableContract) {
            return $transformable->directTransform();
        }

        return $transformable;
    }

    /**
     * @param \Illuminate\Support\Collection $transformable
     *
     * @return \Illuminate\Support\Collection
     */
    protected function transformCollection(Collection $transformable)
    {
        return $transformable->map(function ($model) {
            return $this->transform($model);
        });
    }

    /**
     * @param \Larapie\Contracts\TransformableContract $transformable
     *
     * @return array
     */
    protected function transformTransformable(TransformableContract $transformable)
    {
        $transformer = $transformable->getTransformerClass();

        return $transformer->transform($transformable);
    }
}
