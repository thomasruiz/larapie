<?php

namespace Larapie\Contracts;

interface DirectTransformableContract
{
    /**
     * Transform the instance to a serializable array.
     *
     * @return array
     */
    public function directTransform();
}
