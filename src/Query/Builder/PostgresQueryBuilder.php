<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\Query;
use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\Resolver\PostgresColumnDefinitionResolver;

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

        return $this->buildFinalQuery($queryString);
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

        return $this->buildFinalQuery($queryString, $bindParameters);
    }

    protected function buildDeleteQuery()
    {
        $tableName = $this->buildTableNameWithAlias($this->tableName);
        $queryString = 'delete from ' . $tableName;
        $bindParameters = [];
        $appendWhereConditions = false;
        
        if (!is_null($this->order) || !is_null($this->limit))
        {
            $subQuery = new Query('select ' . $this->primaryKey . ' from ' . $tableName);
            $this->appendWhereConditions($subQuery);
            $this->appendOptions($subQuery);

            $queryString .= ' where ' . $this->primaryKey . ' in ' . '(' . $subQuery->getSql() . ')';

            foreach ($subQuery->getBindParameters() as $key => $value)
            {
                $bindParameters[$key] = $value;
            }
        }
        else 
        {
            $appendWhereConditions = true;
        }
    
        return $this->buildFinalQuery($queryString, $bindParameters, $appendWhereConditions, false);
    }

    protected function buildUpdateQuery()
    {
        $updateValues = [];
        $bindParameters = [];
        $appendWhereConditions = false;
        $tableName = $this->buildTableNameWithAlias($this->tableName);

        foreach ($this->columnData as $key => $value)
        {
            $placeholder = $this->buildPlaceholderWithAlias($key);
            $updateValues[] = $key . ' = :' . $placeholder;
            $bindParameters[$placeholder] = $value;
        }

        $queryString = 'update ' . $tableName . ' set ' . implode(', ', $updateValues);

        if (!is_null($this->order) || !is_null($this->limit))
        {
            $subQuery = new Query('select ' . $this->primaryKey . ' from ' . $tableName);
            $this->appendWhereConditions($subQuery);
            $this->appendOptions($subQuery);

            $queryString .= ' where ' . $this->primaryKey . ' in ' . '(' . $subQuery->getSql() . ')';

            foreach ($subQuery->getBindParameters() as $key => $value)
            {
                $bindParameters[$key] = $value;
            }
        }
        else 
        {
            $appendWhereConditions = true;
        }

        return $this->buildFinalQuery($queryString, $bindParameters, $appendWhereConditions, false);
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

        return $this->buildFinalQuery($queryString, $this->columnData, false, false);
    }

    protected function buildColumnQuery()
    {
        $queryString = 'select column_name as "Field" from information_schema.columns where table_schema = \'' . $this->getSchema() . '\' and table_name = \'' . $this->tableName . '\'';

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildColumnWithAlias($column, $alias = null)
    {
        return (!is_null($alias)) ? ($alias . '.' . $column . " as \"" . $alias . '.' . $column . "\"") : $column;
    }

    protected function buildCreateIndexQuery()
    {
        $queryString = 'create index ' . $this->indexName . ' on ' . $this->tableNameWithSchema($this->tableName) . ' (' . implode(', ', $this->columns) . ')';
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildIndexDefinition(Index $index)
    {
        return null;
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

    protected function buildDropForeignKeyQuery()
    {
        $queryString = 'alter table ' . $this->tableNameWithSchema($this->tableName) . ' drop constraint ' . $this->key->getName();
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function wrapColumnName($columnName)
    {
        return $columnName;
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