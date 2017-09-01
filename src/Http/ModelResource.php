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
     * @var bool
     */
    private $authorization;

    /**
     * ModelResource constructor.
     *
     * @param array $parents
     * @param string $model
     * @param string $name
     * @param bool $authorization
     */
    public function __construct($parents, $model, $name, $authorization)
    {
        $this->parents = $parents;
        $this->model = $model;
        $this->name = $name;
        $this->authorization = $authorization;
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

    /**
     * @return bool
     */
    public function hasAuthorization()
    {
        return $this->authorization;
    }
}
