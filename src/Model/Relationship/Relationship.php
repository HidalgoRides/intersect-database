<?php

namespace Intersect\Database\Model\Relationship;

abstract class Relationship {

    private $attribute;
    private $column;
    private $modelClass;

    public function __construct($modelClass, $column, $attribute)
    {
        $this->modelClass = $modelClass;
        $this->column = $column;
        $this->attribute = $attribute;
    }

    public function getAttribute()
    {
        return $this->attribute;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getModelClass()
    {
        return $this->modelClass;
    }

}