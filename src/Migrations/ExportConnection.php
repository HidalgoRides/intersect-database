<?php

namespace Intersect\Database\Migrations;

use Intersect\Database\Query\Result;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Migrations\ExportQueryBuilder;

class ExportConnection extends Connection {

    /** @var Connection */
    private $connection;

    private $queries = [];

    public function __construct(Connection $connection)
    {
        parent::__construct($connection->getConnectionSettings());
        $this->pdoDriver = $connection->getDriver();
        $this->connection = $connection;
    }

    public function getQueries()
    {
        return $this->queries;
    }
 
    public function getQueryBuilder()
    {
        return new ExportQueryBuilder($this, $this->connection->getQueryBuilder());
    }

    public function switchDatabase($databaseName)
    {
        $this->connection->switchDatabase($databaseName);
    }

    public function query($sql, $bindParameters = [], $bypassCache = false)
    {
        if (count($bindParameters) > 0)
        {
            foreach ($bindParameters as $key => $value)
            {
                $sql = str_replace(':' . $key, "'" . addslashes($value) . "'", $sql);
            }
        }

        $this->queries[] = $sql;
        return new Result();
    }
    
}