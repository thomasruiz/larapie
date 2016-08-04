<?php

namespace Larapie\Contracts;

interface TransformableContract
{
    /**
     * @return \Larapie\Contracts\TransformerContract
     */
    public function getTransformerClass();
}
