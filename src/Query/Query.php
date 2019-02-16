<?php

namespace Intersect\Database\Query;

class Query {

    private $sql;
    private $bindParameters = [];

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

}