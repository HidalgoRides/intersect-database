<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Result;
use Intersect\Database\Connection\ConnectionSettings;
use Intersect\Database\Query\Builder\NullQueryBuilder;

class NullConnection extends Connection {

    public function __construct(ConnectionSettings $connectionSettings = null) {}

    public function getConnection() {}

    public function getQueryBuilder()
    {
        return new NullQueryBuilder($this);
    }

    /**
     * @param $databaseName
     */
    public function switchDatabase($databaseName) {}

    /**
     * @param Query $query
     * @param boolean $bypassCache
     * @return Result
     * @throws DatabaseException
     */
    public function run(Query $query, $bypassCache = false)
    {
        return $this->query($query->getSql(), $query->getBindParameters(), $bypassCache);
    }

    /**
     * @param $sql
     * @param boolean $bypassCache
     * @param array $bindParameters
     * @return Result
     */
    public function query($sql, $bindParameters = [], $bypassCache = false)
    {
        return new Result();
    }

}