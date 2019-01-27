<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Query\Query;

class DefaultQueryBuilder extends AbstractQueryBuilder {

    /**
     * @return Query
     */
    public function buildDeleteQuery()
    {
        $sql = "DELETE FROM " . $this->wrapValue($this->tableName);

        $query = new Query($sql);
        $this->appendWhereConditions($query);
        $this->appendQueryOptions($query);

        return $query;
    }

    /**
     * @param array $columnValueMap
     * @return Query
     */
    public function buildInsertQuery(array $columnValueMap)
    {
        $columns = [];
        $values = [];

        foreach ($columnValueMap as $key => $value)
        {
            $columns[] = $key;
            $values[] = $this->buildPlaceholder($key);
        }

        $sql = "INSERT INTO " . $this->wrapValue($this->tableName) . "(" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";

        return new Query($sql, $columnValueMap);
    }

    /**
     * @param array $columns
     * @return Query
     */
    public function buildSelectQuery(array $columns = [])
    {
        if (count($columns) == 0)
        {
            $columns = ['*'];
        }

        $alias = AliasFactory::getAlias($this->tableName);

        foreach ($columns as &$column)
        {
            $column = $alias . '.' . $column;
        }

        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->wrapValue($this->tableName) . ' as ' . $alias;

        $query = new Query();
        $query->setSql($sql);

        $this->appendWhereConditions($query, true);
        $this->appendQueryOptions($query);

        return $query;
    }

    /**
     * @param array $columnValueMap
     * @param array $primaryKeyColumns
     * @return Query
     */
    public function buildUpdateQuery(array $columnValueMap, array $primaryKeyColumns)
    {
        $updateValues = [];

        foreach ($columnValueMap as $key => $value)
        {
            if (in_array($key, $primaryKeyColumns))
            {
                continue;
            }

            $updateValues[] = $key . '=' . $this->buildPlaceholder($key);
        }

        $sql = "UPDATE " . $this->wrapValue($this->tableName) . " SET " . implode(',', $updateValues);

        if (count($primaryKeyColumns) > 0)
        {
            $primaryKeyColumnCount = 0;
            foreach ($primaryKeyColumns as $primaryKeyColumn)
            {
                $prefix = ($primaryKeyColumnCount == 0) ? ' WHERE ' : ' AND ';
                $sql .= $prefix . $primaryKeyColumn . '=' . $this->buildPlaceholder($primaryKeyColumn);
                $primaryKeyColumnCount++;
            }
        }

        $sql .= ' LIMIT 1';

        return new Query($sql, $columnValueMap);
    }

}