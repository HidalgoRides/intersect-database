<?php

namespace Intersect\Database\Migrations;

use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\ColumnBlueprint;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Schema\Resolver\ColumnDefinitionResolver;

class ExportQueryBuilder extends QueryBuilder {

    /** @var Connection */
    protected $connection;

    /** @var QueryBuilder */
    private $queryBuilder;

    public function __construct(Connection $connection, QueryBuilder $queryBuilder)
    {
        parent::__construct($connection);
        $this->connection = $connection;
        $this->queryBuilder = $queryBuilder;
    }

    public function getAction()
    {
        return $this->queryBuilder->action;
    }

    public function getTableName()
    {
        return $this->queryBuilder->tableName;
    }

    public function build()
    {
        return $this->queryBuilder->build();
    }

    public function createTable(Blueprint $blueprint)
    {
        $this->queryBuilder->createTable($blueprint);
        return $this;
    }

    public function createTableIfNotExists(Blueprint $blueprint)
    {
        $this->queryBuilder->createTableIfNotExists($blueprint);
        return $this;
    }

    public function dropTable($tableName)
    {
        $this->queryBuilder->dropTable($tableName);
        return $this;
    }

    public function dropTableIfExists($tableName)
    {
        $this->queryBuilder->dropTableIfExists($tableName);
        return $this;
    }

    public function dropColumns(array $columns)
    {
        $this->queryBuilder->dropColumns($columns);
        return $this;
    }

    public function addColumn(ColumnBlueprint $columnBlueprint)
    {
        $this->queryBuilder->addColumn($columnBlueprint);
        return $this;
    }

    public function createIndex(array $columns, $indexName)
    {
        $this->queryBuilder->createIndex($columns, $indexName);
        return $this;
    }

    public function dropIndex($indexName)
    {
        $this->queryBuilder->dropIndex($indexName);
        return $this;
    }

    public function select(array $columns = [], QueryParameters $queryParameters = null)
    {
        $this->queryBuilder->select($columns, $queryParameters);
        return $this;
    }

    public function selectMax($column, QueryParameters $queryParameters = null)
    {
        $this->queryBuilder->selectMax($column, $queryParameters);
        return $this;
    }

    public function count(QueryParameters $queryParameters = null)
    {
        $this->queryBuilder->count($queryParameters);
        return $this;
    }

    public function delete(QueryParameters $queryParameters = null)
    {
        $this->queryBuilder->delete($queryParameters);
        return $this;
    }

    public function update(array $columnData, QueryParameters $queryParameters = null)
    {
        $this->queryBuilder->update($columnData, $queryParameters);
        return $this;
    }

    public function insert(array $columnData)
    {
        $this->queryBuilder->insert($columnData);
        return $this;
    }

    public function columns()
    {
        $this->queryBuilder->columns();
        return $this;
    }

    public function table($tableName, $primaryKey = 'id', $alias = null)
    {
        $this->queryBuilder->table($tableName, $primaryKey, $alias);
        return $this;
    }

    public function limit($limit)
    {
        $this->queryBuilder->limit($limit);
        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->queryBuilder->orderBy($column, $direction);
        return $this;
    }

    public function schema($schema)
    {
        $this->queryBuilder->schema($schema);
        return $this;
    }

    public function addQueryCondition(QueryCondition $queryCondition)
    {
        $this->queryBuilder->addQueryCondition($queryCondition);
        return $this;
    }

    public function addQueryConditions(array $queryConditions)
    {
        $this->queryBuilder->addQueryConditions($queryConditions);
        return $this;
    }

    public function whereEquals($column, $value)
    {
        $this->queryBuilder->whereEquals($column, $value);
        return $this;
    }

    public function whereNull($column)
    {
        $this->queryBuilder->whereNull($column);
        return $this;
    }

    public function whereNotNull($column)
    {
        $this->queryBuilder->whereNotNull($column);
        return $this;
    }

    public function whereNotEquals($column, $value)
    {
        $this->queryBuilder->whereNotEquals($column, $value);
        return $this;
    }

    public function whereLike($column, $value)
    {
        $this->queryBuilder->whereLike($column, $value);
        return $this;
    }

    public function whereIn($column, $values, $quoteValues = false)
    {
        $this->queryBuilder->whereIn($column, $values, $quoteValues);
        return $this;
    }

    public function whereBetween($column, $startValue, $endValue)
    {
        $this->queryBuilder->whereBetween($column, $startValue, $endValue);
        return $this;
    }
    
    public function whereBetweenDates($column, $startDate, $endDate)
    {
        $this->queryBuilder->whereBetweenDates($column, $startDate, $endDate);
        return $this;
    }
    
    public function joinLeft($joinTableName, $joinColumnName, $originalColumnName, array $joinColumns = [], $joinTableAlias = null)
    {
        $this->queryBuilder->joinLeft($joinTableName, $joinColumnName, $originalColumnName, $joinColumns, $joinTableAlias);
        return $this;
    }

    protected function buildSelectQuery() 
    {
        return $this->queryBuilder->buildSelectQuery();
    }

    protected function buildInsertQuery() 
    {
        return $this->queryBuilder->buildInsertQuery();
    }

    protected function buildUpdateQuery() 
    {
        return $this->queryBuilder->buildUpdateQuery();
    }

    protected function buildDeleteQuery() 
    {
        return $this->queryBuilder->buildDeleteQuery();
    }

    protected function buildCountQuery() 
    {
        return $this->queryBuilder->buildCountQuery();
    }

    protected function buildColumnQuery() 
    {
        return $this->queryBuilder->buildColumnQuery();
    }
    
    protected function buildIndexDefinition(Index $index) 
    {
        return $this->queryBuilder->buildIndexDefinition($index);
    }

    protected function buildForeignKeyDefinition(ForeignKey $foreignKey) 
    {
        return $this->queryBuilder->buildForeignKeyDefinition($foreignKey);
    }
    
    protected function buildPrimaryKeyDefinition(PrimaryKey $primaryKey) 
    {
        return $this->queryBuilder->buildPrimaryKeyDefinition($primaryKey);
    }
    
    protected function buildUniqueKeyDefinition(UniqueKey $uniqueKey) 
    {
        return $this->queryBuilder->buildUniqueKeyDefinition($uniqueKey);
    }

     /** @return ColumnDefinitionResolver */
    protected function getColumnDefinitionResolver() 
    {
        return $this->queryBuilder->getColumnDefinitionResolver();
    }
    
}