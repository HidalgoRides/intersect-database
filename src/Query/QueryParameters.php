<?php

namespace Intersect\Database\Query;

class QueryParameters {

    private $columns = [];
    private $limit;
    private $order;
    private $whereConditions = [];

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

    public function between($key, $startValue, $endValue)
    {
        $this->whereConditions[] = [$key, 'BETWEEN', [$startValue, $endValue]];
    }
    
    public function betweenDates($key, $startDate, $endDate)
    {
        $this->whereConditions[] = [$key, 'BETWEEN DATES', [$startDate, $endDate]];
    }

    public function equals($key, $value)
    {
        $this->whereConditions[] = [$key, '=', $value];

        return $this;
    }

    public function notEquals($key, $value)
    {
        $this->whereConditions[] = [$key, '!=', $value];

        return $this;
    }

    public function like($key, $value)
    {
        $this->whereConditions[] = [$key, 'LIKE', $value];

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
    }

    public function isNull($key)
    {
        $this->whereConditions[] = [$key, 'IS NULL'];

        return $this;
    }

    public function isNotNull($key)
    {
        $this->whereConditions[] = [$key, 'IS NOT NULL'];

        return $this;
    }

}