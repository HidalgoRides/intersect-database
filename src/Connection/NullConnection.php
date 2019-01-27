<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Result;
use Intersect\Database\Connection\ConnectionSettings;

class NullConnection extends Connection {

    public function __construct(ConnectionSettings $connectionSettings = null) {}

    public function getConnection() {}

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