<?php

namespace Intersect\Database\Query;

use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Query\Builder\Condition\EqualsCondition;
use Intersect\Database\Query\Builder\Condition\NotEqualsCondition;
use Intersect\Database\Query\Builder\Condition\BetweenDatesCondition;
use Intersect\Database\Query\Builder\Condition\BetweenCondition;
use Intersect\Database\Query\Builder\Condition\LikeCondition;
use Intersect\Database\Query\Builder\Condition\InCondition;
use Intersect\Database\Query\Builder\Condition\NullCondition;
use Intersect\Database\Query\Builder\Condition\NotNullCondition;

class QueryParameters {

    private $columns = [];
    private $limit;
    private $order;
    private $whereConditions = [];
    private $queryConditions = [];

    public function __construct()
    {
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns($columns = [])
    {
        $this->columns = $columns;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    public function getWhereConditions()
    {
        return $this->whereConditions;
    }

    /** @return QueryCondition[] */
    public function getQueryConditions()
    {
        return $this->queryConditions;
    }

    public function between($key, $startValue, $endValue)
    {
        $this->whereConditions[] = [$key, 'BETWEEN', [$startValue, $endValue]];
        $this->queryConditions[] = new BetweenCondition($key, $startValue, $endValue);
        return $this;
    }
    
    public function betweenDates($key, $startDate, $endDate)
    {
        $this->whereConditions[] = [$key, 'BETWEEN DATES', [$startDate, $endDate]];
        $this->queryConditions[] = new BetweenDatesCondition($key, $startDate, $endDate);
        return $this;
    }

    public function equals($key, $value)
    {
        $this->whereConditions[] = [$key, '=', $value];
        $this->queryConditions[] = new EqualsCondition($key, $value);
        return $this;
    }

    public function notEquals($key, $value)
    {
        $this->whereConditions[] = [$key, '!=', $value];
        $this->queryConditions[] = new NotEqualsCondition($key, $value);
        return $this;
    }

    public function like($key, $value)
    {
        $this->whereConditions[] = [$key, 'LIKE', $value];
        $this->queryConditions[] = new LikeCondition($key, $value);
        return $this;
    }

    public function in($key, $values, $quoteValues = false)
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

        $this->whereConditions[] = [$key, 'IN', $values];
        $this->queryConditions[] = new InCondition($key, $values);
        return $this;
    }

    public function isNull($key)
    {
        $this->whereConditions[] = [$key, 'IS NULL'];
        $this->queryConditions[] = new NullCondition($key);
        return $this;
    }

    public function isNotNull($key)
    {
        $this->whereConditions[] = [$key, 'IS NOT NULL'];
        $this->queryConditions[] = new NotNullCondition($key);
        return $this;
    }

}