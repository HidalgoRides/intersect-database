<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\Query;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\MySQLColumnDefinitionResolver;
use Intersect\Database\Schema\ForeignKey;
use Intersect\Database\Schema\Index;

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
        $queryString = 'delete from ' . $this->buildTableNameWithAlias($this->tableName);

        $query = new Query($queryString);

        $this->appendWhereConditions($query);
        $this->appendOptions($query);

        return $query;
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

        $query = new Query($queryString, $bindParameters);

        $this->appendWhereConditions($query);
        $this->appendOptions($query);

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
        $queryString = 'show columns from ' . $this->buildTableNameWithAlias($this->tableName);

        return new Query($queryString);
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

    protected function buildForeignKeyDefinition($keyName, ForeignKey $foreignKey)
    {
        return 'constraint ' . $keyName . ' foreign key (`' . $foreignKey->getFromColumn() . '`) references ' . $this->wrapTableName($foreignKey->getOnTable()) . ' (`' . $foreignKey->getToColumn() . '`)';
    }

    protected function buildPrimaryKeyDefinition($keyName, array $columnNames)
    {
        foreach ($columnNames as &$columnName)
        {
            $columnName = '`' . $columnName . '`';
        }

        return 'primary key (' . implode(', ', $columnNames) . ')';
    }

    protected function buildUniqueKeyDefinition($keyName, array $columnNames)
    {
        foreach ($columnNames as &$columnName)
        {
            $columnName = '`' . $columnName . '`';
        }

        return 'unique key ' . $keyName . ' (' . implode(', ', $columnNames) . ')';
    }

}