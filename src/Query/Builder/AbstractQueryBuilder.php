<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Query\Query;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Schema\Table;

abstract class AbstractQueryBuilder {

    /** @var QueryParameters */
    protected $queryParameters;

    protected $tableName;

    public function __construct($tableName = null, QueryParameters $queryParameters = null)
    {
        $this->tableName = $tableName;
        $this->queryParameters = (is_null($queryParameters)) ? new QueryParameters() : $queryParameters;

        return $this;
    }

    abstract public function buildDeleteQuery();
    abstract public function buildInsertQuery(array $columnValueMap);
    abstract public function buildSelectQuery(array $columns);
    abstract public function buildUpdateQuery(array $columnValueMap, array $primaryKeyColumns);

    /**
     * @return Query
     */
    public function buildColumnListQuery()
    {
        $sql = "SHOW COLUMNS FROM " . $this->wrapValue($this->tableName);

        return new Query($sql);
    }

    public function buildCreateTableQuery(Table $table)
    {
        $sql = 'CREATE TABLE ' . $this->wrapValue($table->getName()) . ' (';

        $columnSize = count($table->getColumns());
        $columnIndex = 1;
        foreach ($table->getColumns() as $column)
        {
            $sql .= $this->wrapValue($column->getName()) . ' ' . $column->getType();

            if (!$column->getAllowsNull())
            {
                $sql .= ' NOT NULL';
            }
            else
            {
                $sql .= ' NULL';
            }

            if (!is_null($column->getDefaultValue()))
            {
                $sql .= ' DEFAULT ' . $column->getDefaultValue();
            }

            if ($column->getIsAutoIncrement())
            {
                $sql .= ' AUTO_INCREMENT PRIMARY KEY';
            }

            if ($columnIndex < $columnSize)
            {
                $sql .= ', ';
            }

            $columnIndex++;
        }

        $sql .= ') ENGINE=' . $table->getEngine() . ' DEFAULT CHARSET=' . $table->getCharset();

        return new Query($sql);
    }

    /**
     * @param $allColumns
     * @param $columnList
     * @param $columnPrefix
     */
    protected function addNamedColumns(&$allColumns, $columnList, $columnPrefix)
    {
        foreach ($columnList as $column)
        {
            $allColumns[] = $columnPrefix. '.' . $column . " as '" . $columnPrefix . "." . $column . "'";
        }
    }

    /**
     * @param Query $query
     */
    protected function appendQueryOptions(Query $query)
    {
        $sql = $query->getSql();
        $queryParameters = $this->queryParameters;

        if (!is_null($queryParameters))
        {
            if (!is_null($queryParameters->getOrder()))
            {
                $sql .= ' ORDER BY ' . $queryParameters->getOrder();
            }

            if (!is_null($queryParameters->getLimit()))
            {
                $sql .= ' LIMIT ' . $queryParameters->getLimit();
            }

            $query->setSql($sql);
        }
    }

    /**
     * @param Query $query
     * @param bool $isSelectQuery
     */
    protected function appendWhereConditions(Query $query, $isSelectQuery = false)
    {
        $sql = $query->getSql();

        $whereConditions = $this->queryParameters->getWhereConditions();

        if (count($whereConditions) > 0)
        {
            $columnPrefix = ($isSelectQuery ? AliasFactory::getAlias($this->tableName) : $this->tableName);
            $specialActions = ['IS NULL', 'IS NOT NULL', 'IN', 'BETWEEN', 'BETWEEN DATES'];
            $whereConditionCount = 0;
            foreach ($whereConditions as $whereCondition)
            {
                $key = $whereCondition[0];
                $bindParameterKey = $columnPrefix . '_' . $key;

                $prefix = ($whereConditionCount == 0) ? ' WHERE ' : ' AND ';

                $originalAction = $whereCondition[1];
                $action = $originalAction;

                if ($action == 'BETWEEN DATES')
                {
                    $action = 'BETWEEN';
                }

                $parameterValue = $whereCondition[2];
                $requiresPlaceholder = (!in_array($action, $specialActions));

                $sql .= $prefix . $columnPrefix . '.' . $key . ' ' . $action;

                if ($requiresPlaceholder)
                {
                    $sql .= ' ' . $this->buildPlaceholder($bindParameterKey);
                    $query->bindParameter($bindParameterKey, $parameterValue);
                }
                else
                {
                    if ($action == 'IN')
                    {
                        $sql .= ' (' . implode(',', $parameterValue) . ')';
                    }
                    else if ($action == 'BETWEEN')
                    {
                        if ($originalAction == 'BETWEEN DATES')
                        {
                            $sql .= ' CAST(\'' . $parameterValue[0] . '\' AS DATETIME) AND CAST(\'' . $parameterValue[1] . '\' AS DATETIME)';
                        }
                        else
                        {
                            $sql .= ' ' . $parameterValue[0] . ' AND ' . $parameterValue[1];
                        }
                    }
                }

                $whereConditionCount++;
            }

            $query->setSql($sql);
        }
    }

    /**
     * @param $key
     * @return string
     */
    protected function buildPlaceholder($key)
    {
        return ':' . $key;
    }

    /**
     * @param $value
     * @return string
     */
    protected function wrapValue($value)
    {
        return '`' . $value . '`';
    }

}