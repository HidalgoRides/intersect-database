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
     * @return Result
     * @throws DatabaseException
     */
    public function run(Query $query)
    {
        return $this->query($query->getSql(), $query->getBindParameters());
    }

    /**
     * @param $sql
     * @param array $bindParameters
     * @return Result
     */
    public function query($sql, $bindParameters = [])
    {
        return new Result();
    }

}