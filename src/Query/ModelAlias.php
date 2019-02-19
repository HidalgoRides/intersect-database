<?php

namespace Intersect\Database\Query;

use Intersect\Database\Model\AbstractModel;

class ModelAlias {

    private $key;

    /** @var string */
    private $modelClassName;

    public function __construct(AbstractModel $model, $key)
    {
        $this->modelClassName = get_class($model);
        $this->key = $key;
    }

    /** @return string */
    public function getKey()
    {
        return $this->key;
    }

    /** @return string */
    public function getModelClassName()
    {
        return $this->modelClassName;
    }

}