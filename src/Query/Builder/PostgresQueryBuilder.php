<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\Query;
use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\PostgresColumnDefinitionResolver;

class PostgresQueryBuilder extends QueryBuilder {

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    protected function getColumnDefinitionResolver()
    {
        return new PostgresColumnDefinitionResolver();
    }

    protected function buildCountQuery()
    {
        $queryString = 'select count(*) as count from ' . $this->wrapTableName($this->tableName);

        $query = new Query($queryString);

        $this->appendWhereConditions($query);
        $this->appendOptions($query);

        return $query;
    }

    protected function buildSelectQuery()
    {
        $alias = $this->getTableAlias();
        $columns = $this->columns;
        $allNamedColumns = [];
        
        $this->addNamedColumns($allNamedColumns, $columns, $alias);

        foreach ($this->joinQueries as $joinQuery)
        {
            foreach ($joinQuery['columns'] as $column)
            {
                $allNamedColumns[] = $column;
            }
        }

        $bindParameters = [];
        $queryString = 'select ' . implode(', ', $allNamedColumns) . ' from ' . $this->buildTableNameWithAlias($this->tableName, $alias);

        foreach ($this->joinQueries as $joinQuery)
        {
            $queryString .= ' ' . $joinQuery['queryString'];

            foreach ($joinQuery['bindParameters'] as $bindParameterKey => $bindParameterValue)
            {
                $bindParameters[$bindParameterKey] = $bindParameterValue;
            }
        }

        $query = new Query($queryString, $bindParameters);

        $this->appendWhereConditions($query);
        $this->appendOptions($query);

        return $query;
    }

    protected function buildDeleteQuery()
    {
        $tableName = $this->buildTableNameWithAlias($this->tableName);
        $queryString = 'delete from ' . $tableName;

        $query = new Query($queryString);

        if (!is_null($this->order) || !is_null($this->limit))
        {
            $subQuery = new Query('select ' . $this->primaryKey . ' from ' . $tableName);
            $this->appendWhereConditions($subQuery);
            $this->appendOptions($subQuery);

            $sql = $query->getSql() . ' where ' . $this->primaryKey . ' in ' . '(' . $subQuery->getSql() . ')';
            $query->setSql($sql);

            foreach ($subQuery->getBindParameters() as $key => $value)
            {
                $query->bindParameter($key, $value);
            }
        }
        else 
        {
            $this->appendWhereConditions($query);
        }
    
        return $query;
    }

    protected function buildUpdateQuery()
    {
        $updateValues = [];
        $bindParameters = [];
        $tableName = $this->buildTableNameWithAlias($this->tableName);

        foreach ($this->columnData as $key => $value)
        {
            $placeholder = $this->buildPlaceholderWithAlias($key);
            $updateValues[] = $key . ' = :' . $placeholder;
            $bindParameters[$placeholder] = $value;
        }

        $queryString = 'update ' . $tableName . ' set ' . implode(', ', $updateValues);

        $query = new Query($queryString, $bindParameters);

        if (!is_null($this->order) || !is_null($this->limit))
        {
            $subQuery = new Query('select ' . $this->primaryKey . ' from ' . $tableName);
            $this->appendWhereConditions($subQuery);
            $this->appendOptions($subQuery);

            $sql = $query->getSql() . ' where ' . $this->primaryKey . ' in ' . '(' . $subQuery->getSql() . ')';
            $query->setSql($sql);

            foreach ($subQuery->getBindParameters() as $key => $value)
            {
                $query->bindParameter($key, $value);
            }
        }
        else 
        {
            $this->appendWhereConditions($query);
        }

        return $query;
    }

    protected function buildInsertQuery()
    {
        $columns = [];
        $values = [];

        foreach ($this->columnData as $key => $value)
        {
            $columns[] = $key;
            $values[] = ':' . $this->buildPlaceholderWithAlias($key);
        }

        $queryString = 'insert into ' . $this->buildTableNameWithAlias($this->tableName) . ' (' . implode(', ', $columns) . ') values (' . implode(', ', $values) . ')';

        return new Query($queryString, $this->columnData);
    }

    protected function buildColumnQuery()
    {
        $queryString = 'select column_name as "Field" from information_schema.columns where table_schema = \'' . $this->getSchema() . '\' and table_name = \'' . $this->tableName . '\'';

        return new Query($queryString);
    }

    protected function buildColumnWithAlias($column, $alias = null)
    {
        return (!is_null($alias)) ? ($alias . '.' . $column . " as \"" . $alias . '.' . $column . "\"") : $column;
    }

    protected function buildIndexDefinition(Index $index)
    {
        throw new \Exception('Not implemented');
    }

    protected function buildForeignKeyDefinition(ForeignKey $foreignKey)
    {
        return 'constraint ' . $foreignKey->getName() . ' foreign key (' . $foreignKey->getFromColumn() . ') references ' . $foreignKey->getTableSchema() . '.' . $foreignKey->getOnTable() . ' (' . $foreignKey->getToColumn() . ')';
    }

    protected function buildPrimaryKeyDefinition(PrimaryKey $primaryKey)
    {
        return 'constraint ' . $primaryKey->getName() . ' primary key (' . implode(', ', $primaryKey->getColumns()) . ')';
    }

    protected function buildUniqueKeyDefinition(UniqueKey $uniqueKey)
    {
        return 'constraint ' . $uniqueKey->getName() . ' unique (' . implode(', ', $uniqueKey->getColumns()) . ')';
    }

    protected function wrapTableName($tableName)
    {
        return $this->tableNameWithSchema($tableName);
    }

    private function tableNameWithSchema($tableName)
    {
        return $this->getSchema() . '.' . $tableName;
    }

    private function getSchema()
    {
        return (!is_null($this->schema) ? $this->schema : 'public');
    }

}