<?php

namespace Larapie\Contracts;

interface TransformableContract
{
    /**
     * Return an instance of the transformer for this class.
     *
     * @return \Larapie\Contracts\TransformerContract
     */
    public function getTransformerClass();
}
