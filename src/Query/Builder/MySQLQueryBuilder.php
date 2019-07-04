<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\Resolver\MySQLColumnDefinitionResolver;

class MySQLQueryBuilder extends QueryBuilder {

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    protected function getColumnDefinitionResolver()
    {
        return new MySQLColumnDefinitionResolver();
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
        $queryString = 'delete from ' . $this->buildTableNameWithAlias($this->tableName);

        return $this->buildFinalQuery($queryString);
    }

    protected function buildUpdateQuery()
    {
        $updateValues = [];
        $bindParameters = [];

        foreach ($this->columnData as $key => $value)
        {
            $placeholder = $this->buildPlaceholderWithAlias($key);
            $updateValues[] = $key . ' = :' . $placeholder;
            $bindParameters[$placeholder] = $value;
        }

        $queryString = 'update ' . $this->buildTableNameWithAlias($this->tableName) . ' set ' . implode(', ', $updateValues);

        return $this->buildFinalQuery($queryString, $bindParameters);
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
        $queryString = 'show columns from ' . $this->buildTableNameWithAlias($this->tableName);

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildIndexDefinition(Index $index)
    {
        $columns = $index->getColumns();

        foreach ($columns as &$column)
        {
            $column = '`' . $column . '`';
        }

        return 'index (' . implode(', ', $columns) . ')';
    }

    protected function buildForeignKeyDefinition(ForeignKey $foreignKey)
    {
        return 'constraint ' . $foreignKey->getName() . ' foreign key (`' . $foreignKey->getFromColumn() . '`) references ' . $this->wrapTableName($foreignKey->getOnTable()) . ' (`' . $foreignKey->getToColumn() . '`)';
    }

    protected function buildPrimaryKeyDefinition(PrimaryKey $primaryKey)
    {
        $columns = $primaryKey->getColumns();

        foreach ($columns as &$column)
        {
            $column = '`' . $column . '`';
        }

        return 'primary key (' . implode(', ', $columns) . ')';
    }

    protected function buildUniqueKeyDefinition(UniqueKey $uniqueKey)
    {
        $columns = $uniqueKey->getColumns();

        foreach ($columns as &$column)
        {
            $column = '`' . $column . '`';
        }

        return 'unique key ' . $uniqueKey->getName() . ' (' . implode(', ', $columns) . ')';
    }

    protected function buildCreateTableOptions() 
    {
        $tableOptions = $this->blueprint->getTableOptions();
        $tableOptionsArray = [];

        $this->appendNonNullValueToArray($tableOptionsArray, 'engine', $tableOptions->getEngine());
        $this->appendNonNullValueToArray($tableOptionsArray, 'charset', $tableOptions->getCharacterSet());
        $this->appendNonNullValueToArray($tableOptionsArray, 'collate', $tableOptions->getCollation());

        return (count($tableOptionsArray) > 0) ? implode(' ', $tableOptionsArray) : null;
    }

    protected function buildDropIndexQuery()
    {
        $queryString = 'alter table ' . $this->wrapTableName($this->tableName) . ' drop index ' . $this->indexName;
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    private function appendNonNullValueToArray(array &$array, $key, $value)
    {
        if (!is_null($value))
        {
            $array[] = $key . '=' . $value;
        }
    }

}