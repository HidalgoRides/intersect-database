<?php

namespace Intersect\Database\Schema;

use Closure;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\ColumnBlueprint;
use Intersect\Database\Connection\ConnectionRepository;

class Schema {

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection = null)
    {
        if (is_null($connection))
        {
            $connection = ConnectionRepository::get();
        }
        
        $this->connection = $connection;
    }

    public function createTable($tableName, Closure $closure)
    {
        $blueprint = new Blueprint($tableName);

        $closure($blueprint);

        return $this->connection->getQueryBuilder()->createTable($blueprint)->get();
    }

    public function addColumn($tableName, ColumnBlueprint $columnBlueprint)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->addColumn($columnBlueprint)->get();
    }

    public function dropColumns($tableName, array $columns)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->dropColumns($columns)->get();
    }

    public function dropTable($tableName)
    {
        return $this->connection->getQueryBuilder()->dropTable($tableName)->get();
    }

    public function dropTableIfExists($tableName)
    {
        return $this->connection->getQueryBuilder()->dropTableIfExists($tableName)->get();
    }

}