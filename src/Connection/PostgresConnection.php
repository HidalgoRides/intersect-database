<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Query\Builder\NullQueryBuilder;

class PostgresConnection extends Connection {

    protected $pdoDriver = 'pgsql';

    public function getQueryBuilder()
    {
        // TODO: change to PostgresQueryBuilder once implemented
        return new NullQueryBuilder($this);
    }

}