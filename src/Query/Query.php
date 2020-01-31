<?php

namespace Intersect\Database\Query;

use Intersect\Database\Query\Builder\ResolvedQueryCondition;

class Query {

    private $id;
    private $sql;
    private $bindParameters = [];

    public function __construct($sql = null, $bindParameters = [])
    {
        $this->id = uniqid();
        $this->sql = $sql;
        $this->bindParameters = $bindParameters;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param ResolvedQueryCondition $queryCondition
     */
    public function addQueryCondition(ResolvedQueryCondition $queryCondition)
    {
        $this->queryConditions[] = $queryCondition;
    }

    /**
     * @param $sql
     */
    public function appendSql($sql)
    {
        $this->sql .= $sql;
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

    public function __toString()
    {
        $parameterOutput = '';

        $parameters = $this->getBindParameters();
        if (count($parameters) > 0)
        {
            foreach ($parameters as $key => $value)
            {
                $parameterOutput .= PHP_EOL . $key . ' => ' . $value;
            }
        }

        return '[QueryID: ' . $this->getId() . '] ' . PHP_EOL . $this->getSql() . $parameterOutput;
    }

}