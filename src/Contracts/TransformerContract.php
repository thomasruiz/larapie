<?php

namespace Larapie\Contracts;

interface TransformerContract
{
    /**
     * @param \Larapie\Contracts\TransformableContract $model
     *
     * @return array
     */
    public function transform(TransformableContract $model);
}
