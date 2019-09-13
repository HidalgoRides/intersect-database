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

    public function createTableIfNotExists($tableName, Closure $closure)
    {
        $blueprint = new Blueprint($tableName);

        $closure($blueprint);

        return $this->connection->getQueryBuilder()->createTableIfNotExists($blueprint)->get();
    }

    public function addColumn($tableName, ColumnBlueprint $columnBlueprint)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->addColumn($columnBlueprint)->get();
    }

    public function dropColumns($tableName, array $columns)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->dropColumns($columns)->get();
    }

    public function createIndex($tableName, array $columns, $indexName)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->createIndex($columns, $indexName)->get();
    }

    public function dropIndex($tableName, $indexName)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->dropIndex($indexName)->get();
    }

    public function dropTable($tableName)
    {
        return $this->connection->getQueryBuilder()->dropTable($tableName)->get();
    }

    public function dropTableIfExists($tableName)
    {
        return $this->connection->getQueryBuilder()->dropTableIfExists($tableName)->get();
    }

    public function addForeignKey($tableName, $fromColumn, $toColumn, $onTable, $keyName = null)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->addForeignKey($fromColumn, $toColumn, $onTable, null, $keyName)->get();
    }

    public function dropForeignKey($tableName, $keyName)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->dropForeignKey($keyName)->get();
    }

}