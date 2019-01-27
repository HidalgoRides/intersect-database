<?php

namespace Intersect\Database\Query;

use Intersect\Database\Query\QueryRelationship;

class Query {

    private $sql;
    private $bindParameters = [];
    private $relationshipMap = [];

    public function __construct($sql = null, $bindParameters = [])
    {
        $this->sql = $sql;
        $this->bindParameters = $bindParameters;
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param $sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return array
     */
    public function getBindParameters()
    {
        return $this->bindParameters;
    }

    /**
     * @param $key
     * @param $value
     */
    public function bindParameter($key, $value)
    {
        $this->bindParameters[$key] = $value;
    }

    /**
     * @param QueryRelationship $queryRelationship
     */
    public function addRelationship(QueryRelationship $queryRelationship)
    {
        $this->relationshipMap[$queryRelationship->getAlias()] = $queryRelationship;
    }

    public function getRelationshipMap()
    {
        return $this->relationshipMap;
    }

}