<?php

namespace Larapie\Http;

class ModelResource
{
    /**
     * @var array
     */
    private $parents;

    /**
     * @var string
     */
    private $model;

    /**
     * @var string
     */
    private $name;

    /**
     * ModelResource constructor.
     *
     * @param array  $parents
     * @param string $model
     * @param string $name
     */
    public function __construct($parents, $model, $name)
    {
        $this->parents = $parents;
        $this->model = $model;
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
