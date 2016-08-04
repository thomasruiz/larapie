<?php

namespace Larapi\Contracts;

interface TransformableContract
{
    /**
     * @return \Larapi\Contracts\TransformerContract
     */
    public function getTransformerClass();
}
