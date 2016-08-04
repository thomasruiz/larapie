<?php

namespace Larapi\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TransformerContract
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return array
     */
    public function transform(Model $model);
}
