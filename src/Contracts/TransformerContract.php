<?php

namespace Larapie\Contracts;

interface TransformerContract
{
    /**
     * Transform the $model to a serializable array.
     *
     * @param \Larapie\Contracts\TransformableContract $model
     *
     * @return array
     */
    public function transform(TransformableContract $model);
}
