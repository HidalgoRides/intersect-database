<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\QueryConditionVisitor;
use Intersect\Database\Query\Builder\ResolvedQueryCondition;
use Intersect\Database\Query\Builder\QueryConditionVisitable;

abstract class QueryCondition implements QueryConditionVisitable {

    private static $QUERY_PLACEHOLDER_CACHE = [];

    private $column;
    private $value;

    public function __construct($column, $value = null)
    {
        $this->column = $column;
        $this->value = $value;
    }

    public function accept(QueryConditionVisitor $visitor) 
    {
        $visitor->visitQueryCondition($this);
    }

    /**
     * @return ResolvedQueryCondition
     */
    abstract public function resolve(Query $query, $alias = null);

    public function getColumn()
    {
        return $this->column;
    }

    public function getValue()
    {
        return $this->value;
    }

    protected function buildColumnWithAlias($column, $alias)
    {
        return (!is_null($alias)) ? ($alias . '.' . $column) : $column;
    }

    protected function buildPlaceholderWithAlias(Query $query, $key, $alias)
    {
        $placeholder = $key;

        if (!is_null($alias))
        {
            $placeholder = ($alias . '_'. $key);
        }

        $queryId = $query->getId();

        if (!array_key_exists($queryId, self::$QUERY_PLACEHOLDER_CACHE))
        {
            self::$QUERY_PLACEHOLDER_CACHE[$queryId] = [];
        }

        if (!array_key_exists($placeholder, self::$QUERY_PLACEHOLDER_CACHE[$queryId]))
        {
            self::$QUERY_PLACEHOLDER_CACHE[$queryId][$placeholder] = 1;
        }
        else
        {
            $placeholder .= '_' . self::$QUERY_PLACEHOLDER_CACHE[$queryId][$placeholder]++;
        }

        return $placeholder;
    }

}