<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Query\Builder\Condition\EqualsCondition;
use Intersect\Database\Query\Builder\Condition\NotEqualsCondition;
use Intersect\Database\Query\Builder\QueryConditionResolver;
use Intersect\Database\Query\Builder\Condition\NullCondition;
use Intersect\Database\Query\Builder\Condition\NotNullCondition;
use Intersect\Database\Query\Builder\Condition\InCondition;
use Intersect\Database\Query\Builder\Condition\BetweenDatesCondition;
use Intersect\Database\Query\Builder\Condition\BetweenCondition;
use Intersect\Database\Query\Builder\Condition\LikeCondition;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Query\AliasFactory;

class QueryBuilder {

    private static $ACTION_SELECT = 'select';
    private static $ACTION_DELETE = 'delete';
    private static $ACTION_UPDATE = 'update';
    private static $ACTION_INSERT = 'insert';
    private static $ACTION_COLUMNS = 'columns';

    private $action;
    private $columns = ['*'];
    private $columnData = [];
    private $joinQueries = [];
    private $limit;
    private $order;
    private $tableName;
    private $useAliases = false;

    /** @var QueryCondition[] */
    private $queryConditions = [];

    /** @var QueryConditionResolver */
    private $queryConditionResolver;

    /** @var QueryParameters */
    private $queryParameters;

    /** @return QueryBuilder */
    public static function select(array $columns = [], QueryParameters $queryParameters = null)
    {
        $queryBuilder = new static();
        $queryBuilder->action = self::$ACTION_SELECT;
        $queryBuilder->useAliases = true;

        if (count($columns) > 0)
        {
            $queryBuilder->columns = $columns;
        }

        if (!is_null($queryParameters))
        {
            $queryBuilder->initFromQueryParameters($queryParameters);
        }

        return $queryBuilder;
    }

    /** @return QueryBuilder */
    public static function delete(QueryParameters $queryParameters = null)
    {
        $queryBuilder = new static();
        $queryBuilder->action = self::$ACTION_DELETE;

        if (!is_null($queryParameters))
        {
            $queryBuilder->initFromQueryParameters($queryParameters);
        }

        return $queryBuilder;
    }

    /** @return QueryBuilder */
    public static function update(array $columnData, QueryParameters $queryParameters = null)
    {
        $queryBuilder = new static();
        $queryBuilder->action = self::$ACTION_UPDATE;
        $queryBuilder->columnData = $columnData;

        if (!is_null($queryParameters))
        {
            $queryBuilder->initFromQueryParameters($queryParameters);
        }

        return $queryBuilder;
    }

    /** @return QueryBuilder */
    public static function insert(array $columnData)
    {
        $queryBuilder = new static();
        $queryBuilder->action = self::$ACTION_INSERT;
        $queryBuilder->columnData = $columnData;
        return $queryBuilder;
    }

    /** @return QueryBuilder */
    public static function columns()
    {
        $queryBuilder = new static();
        $queryBuilder->action = self::$ACTION_COLUMNS;
        return $queryBuilder;
    }

    private function __construct() 
    {
        $this->queryConditionResolver = new QueryConditionResolver();
    }

    private function initFromQueryParameters(QueryParameters $queryParameters)
    {
        $this->queryParameters = $queryParameters;
        $this->addQueryConditions($queryParameters->getQueryConditions());

        $order = $queryParameters->getOrder();
        if (!is_null($order))
        {
            $orderParts = explode(' ', $queryParameters->getOrder());
            $orderColumn = $orderParts[0];
            $orderDirection = (isset($orderParts[1]) ? $orderParts[1] : 'asc');
            $this->orderBy($orderColumn, $orderDirection);
        }

        $limit = $queryParameters->getLimit();
        if (!is_null($limit))
        {
            $this->limit($limit);
        }
    }

    /** @return QueryBuilder */
    public function table($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /** @return QueryBuilder */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /** @return QueryBuilder */
    public function orderBy($column, $direction = 'asc')
    {
        $this->order = $column . ' ' . strtolower($direction);
        return $this;
    }

    /** @return QueryBuilder */
    public function addQueryCondition(QueryCondition $queryCondition)
    {
        $this->queryConditions[] = $queryCondition;
        return $this;
    }

    /** @return QueryBuilder */
    public function addQueryConditions(array $queryConditions)
    {
        foreach ($queryConditions as $queryCondition)
        {
            $this->addQueryCondition($queryCondition);
        }
        return $this;
    }

    /** @return QueryBuilder */
    public function whereEquals($column, $value)
    {
        $this->queryConditions[] = new EqualsCondition($column, $value);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereNull($column)
    {
        $this->queryConditions[] = new NullCondition($column);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereNotNull($column)
    {
        $this->queryConditions[] = new NotNullCondition($column);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereNotEquals($column, $value)
    {
        $this->queryConditions[] = new NotEqualsCondition($column, $value);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereLike($column, $value)
    {
        $this->queryConditions[] = new LikeCondition($column, $value);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereIn($column, $values, $quoteValues = false)
    {
        if (!is_array($values))
        {
            $values = [$values];
        }

        if ($quoteValues)
        {
            foreach ($values as &$value)
            {
                $value = "'" . $value . "'";
            }
        }

        $this->queryConditions[] = new InCondition($column, $values);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereBetween($column, $startValue, $endValue)
    {
        $this->queryConditions[] = new BetweenCondition($column, $startValue, $endValue);
        return $this;
    }
    
    /** @return QueryBuilder */
    public function whereBetweenDates($column, $startDate, $endDate)
    {
        $this->queryConditions[] = new BetweenDatesCondition($column, $startDate, $endDate);
        return $this;
    }
    
    /** @return QueryBuilder */
    public function joinLeft($joinTableName, $joinColumnName, $originalColumnName, array $joinColumns = [])
    {
        $originalTableAlias = $this->getTableAlias();
        $joinTableAlias = ($this->useAliases) ? AliasFactory::getAlias($joinTableName) : $joinTableName;
        $queryString = 'left join ' . $this->buildTableNameWithAlias($joinTableName, $joinTableAlias) . ' on ' . $originalTableAlias . '.' . $originalColumnName . ' = ' . $joinTableAlias . '.' . $joinColumnName;
        
        $bindParameters = [
            $this->buildPlaceholderWithAlias($joinColumnName, $joinTableAlias) => $joinColumnValue,
            $this->buildPlaceholderWithAlias($originalColumnName, $originalTableAlias) => $originalColumnValue
        ];

        $allNamedColumns = [];
        $this->addNamedColumns($allNamedColumns, $joinColumns, $joinTableAlias);
        
        $this->joinQueries[] = [
            'columns' => $allNamedColumns,
            'queryString' => $queryString,
            'bindParameters' => $bindParameters
        ];
        return $this;
    }

    /** @return Query */
    public function build()
    {
        $query = new Query();

        switch ($this->action)
        {
            case self::$ACTION_SELECT:
                $query = $this->buildSelectQuery();
                break;
            case self::$ACTION_INSERT:
                $query = $this->buildInsertQuery();
                break;
            case self::$ACTION_UPDATE:
                $query = $this->buildUpdateQuery();
                break;
            case self::$ACTION_DELETE:
                $query = $this->buildDeleteQuery();
                break;
            case self::$ACTION_COLUMNS:
                $query = $this->buildColumnQuery();
                break;
        }

        return $query;
    }

    protected function getTableAlias()
    {
        return ($this->useAliases) ? AliasFactory::getAlias($this->tableName) : null;
    }

    private function buildSelectQuery()
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
        }

        $query = new Query($queryString, $bindParameters);

        $this->appendWhereConditions($query);
        $this->appendOptions($query);

        return $query;
    }

    private function addNamedColumns(&$allColumns, $columnList, $alias)
    {
        foreach ($columnList as $column)
        {
            $allColumns[] = $this->buildColumnWithAlias($column, $alias);
        }
    }

    private function buildDeleteQuery()
    {
        $queryString = 'delete from ' . $this->buildTableNameWithAlias($this->tableName);

        $query = new Query($queryString);

        $this->appendWhereConditions($query);
        $this->appendOptions($query);

        return $query;
    }

    private function buildUpdateQuery()
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

    private function buildInsertQuery()
    {
        $columns = [];
        $values = [];

        foreach ($this->columnData as $key => $value)
        {
            $columns[] = $key;
            $values[] = ':' . $this->buildPlaceholderWithAlias($key);
        }

        $queryString = 'insert into ' . $this->buildTableNameWithAlias($this->tableName) . ' (' . implode(', ', $columns) . ') value (' . implode(', ', $values) . ')';

        return new Query($queryString, $this->columnData);
    }

    private function buildColumnQuery()
    {
        $queryString = $sql = 'show columns from ' . $this->buildTableNameWithAlias($this->tableName);

        return new Query($queryString);
    }

    private function appendWhereConditions(Query $query)
    {
        $queryConditions = $this->queryConditions;

        if (count($queryConditions) > 0)
        {
            $sql = $query->getSql();
            $whereSql = '';
            $alias = $this->getTableAlias();
            
            foreach ($queryConditions as $queryCondition)
            {
                $whereSql .= ($whereSql == '') ? ' where ' : ' and ';
                $resolvedQueryCondition = $this->queryConditionResolver->resolve($queryCondition, $alias);
                
                $whereSql .= $resolvedQueryCondition->getQueryString();
                $bindParameters = $resolvedQueryCondition->getBindParameters();
                if (!is_null($bindParameters))
                {
                    $query->bindParameter($bindParameters[0], $bindParameters[1]);
                }
            }

            $query->setSql($sql . $whereSql);
        }
    }

    private function appendOptions(Query $query)
    {
        $sql = $query->getSql();
        $alias = $this->getTableAlias();
        
        if (!is_null($this->order))
        {
            $sql .= ' order by ' . ((!is_null($alias)) ? $alias . '.' : '') . $this->order;
        }

        if (!is_null($this->limit))
        {
            $sql .= ' limit ' . $this->limit;
        }

        $query->setSql($sql);
    }

    private function buildColumnWithAlias($column, $alias = null)
    {
        return (!is_null($alias)) ? ($alias . '.' . $column . " as '" . $alias . '.' . $column . "'") : $column;
    }

    private function buildPlaceholderWithAlias($key, $alias = null)
    {
        return (!is_null($alias)) ? ($alias . '_' . $key) : $key;
    }

    private function buildTableNameWithAlias($tableName, $alias = null)
    {
        $tableName = $this->wrapTableName($tableName);

        return (!is_null($alias)) ? ($tableName . ' as ' . $alias) : $tableName;
    }

    private function wrapTableName($tableName)
    {
        return '`' . $tableName . '`';
    }

}