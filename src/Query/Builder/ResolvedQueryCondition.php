<?php

namespace Intersect\Database\Query\Builder;

class ResolvedQueryCondition {

    private $queryString;
    private $bindParameters = [];

    public function __construct($queryString, array $bindParameters = [])
    {
        $this->queryString = $queryString;
        $this->bindParameters = $bindParameters;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }

    public function getBindParameters()
    {
        return $this->bindParameters;
    }

}