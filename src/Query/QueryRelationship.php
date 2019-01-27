<?php

namespace Intersect\Database\Query;

class QueryRelationship {

    private $alias;
    private $class;
    private $key;

    public function __construct($alias, $class, $key)
    {
        $this->alias = $alias;
        $this->class = $class;
        $this->key = $key;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getKey()
    {
        return $this->key;
    }

}