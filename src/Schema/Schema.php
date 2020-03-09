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

    public function getConnection()
    {
        return $this->connection;
    }

    public function getSchema()
    {
        $schema = null;
        $connectionSettings = $this->connection->getConnectionSettings();

        if (!is_null($connectionSettings))
        {
            $schema = $connectionSettings->getSchema();
        }

        return $schema;
    }

    public function createDatabase($databaseName)
    {
        return $this->connection->getQueryBuilder()->createDatabase($databaseName)->schema($this->getSchema())->get();
    }

    public function dropDatabase($databaseName)
    {
        return $this->connection->getQueryBuilder()->dropDatabase($databaseName)->schema($this->getSchema())->get();
    }

    public function createTable($tableName, Closure $closure)
    {
        $blueprint = new Blueprint($tableName);

        $closure($blueprint);

        return $this->connection->getQueryBuilder()->createTable($blueprint)->schema($this->getSchema())->get();
    }

    public function createTableIfNotExists($tableName, Closure $closure)
    {
        $blueprint = new Blueprint($tableName);

        $closure($blueprint);

        return $this->connection->getQueryBuilder()->createTableIfNotExists($blueprint)->schema($this->getSchema())->get();
    }

    public function addColumn($tableName, ColumnBlueprint $columnBlueprint)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->schema($this->getSchema())->addColumn($columnBlueprint)->get();
    }

    public function dropColumns($tableName, array $columns)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->schema($this->getSchema())->dropColumns($columns)->get();
    }

    public function createIndex($tableName, array $columns, $indexName)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->schema($this->getSchema())->createIndex($columns, $indexName)->get();
    }

    public function dropIndex($tableName, $indexName)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->schema($this->getSchema())->dropIndex($indexName)->get();
    }

    public function dropTable($tableName)
    {
        return $this->connection->getQueryBuilder()->dropTable($tableName)->schema($this->getSchema())->get();
    }

    public function dropTableIfExists($tableName)
    {
        return $this->connection->getQueryBuilder()->dropTableIfExists($tableName)->schema($this->getSchema())->get();
    }

    public function addForeignKey($tableName, $fromColumn, $toColumn, $onTable, $keyName = null)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->schema($this->getSchema())->addForeignKey($fromColumn, $toColumn, $onTable, null, $keyName)->get();
    }

    public function dropForeignKey($tableName, $keyName)
    {
        return $this->connection->getQueryBuilder()->table($tableName)->schema($this->getSchema())->dropForeignKey($keyName)->get();
    }

    public function truncateTable($tableName)
    {
        return $this->connection->getQueryBuilder()->truncateTable($tableName)->schema($this->getSchema())->get();
    }

    public function switchDatabase($databaseName)
    {
        return $this->connection->switchDatabase($databaseName);
    }

}