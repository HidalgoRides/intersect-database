<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Query\Builder\PostgresQueryBuilder;

class PostgresConnection extends Connection {

    protected $pdoDriver = 'pgsql';

    /**
     * @return PostgresQueryBuilder
     */
    public function getQueryBuilder()
    {
        return new PostgresQueryBuilder($this);
    }

    /**
     * @param $databaseName
     * @throws DatabaseException
     */
    public function switchDatabase($databaseName)
    {
        $cs = $this->connectionSettings;

        $this->closeConnection();
        $this->connectionSettings = new ConnectionSettings($cs->getHost(), $cs->getUsername(), $cs->getPassword(), $cs->getPort(), $databaseName, $cs->getCharset());
    }

}