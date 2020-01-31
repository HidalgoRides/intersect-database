<?php

namespace Intersect\Database\Query;

use Closure;
use Intersect\Database\Query\Builder\Condition\InCondition;
use Intersect\Database\Query\Builder\Condition\LikeCondition;
use Intersect\Database\Query\Builder\Condition\NullCondition;
use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Query\Builder\Condition\EqualsCondition;
use Intersect\Database\Query\Builder\Condition\BetweenCondition;
use Intersect\Database\Query\Builder\Condition\NotNullCondition;
use Intersect\Database\Query\Builder\Condition\NotEqualsCondition;
use Intersect\Database\Query\Builder\Condition\QueryConditionType;
use Intersect\Database\Query\Builder\Condition\QueryConditionGroup;
use Intersect\Database\Query\Builder\Condition\BetweenDatesCondition;

class QueryParameters {

    private $columns = [];
    private $limit;
    private $order;
    private $queryConditions = [];
    private $rootConjunction;

    public function __construct($rootConjunction = QueryConditionType::AND)
    {
        $this->rootConjunction = $rootConjunction;
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

    /** @return QueryCondition[] */
    public function getQueryConditions()
    {
        return $this->queryConditions;
    }

    public function getRootConjunction()
    {
        return $this->rootConjunction;
    }

    public function group(Closure $closure)
    {
        $this->addGroupConditions($closure, QueryConditionType::AND);
        return $this;
    }

    public function groupOr(Closure $closure)
    {
        $this->addGroupConditions($closure, QueryConditionType::OR);
        return $this;
    }

    public function between($key, $startValue, $endValue)
    {
        $this->queryConditions[] = new BetweenCondition($key, $startValue, $endValue);
        return $this;
    }
    
    public function betweenDates($key, $startDate, $endDate)
    {
        $this->queryConditions[] = new BetweenDatesCondition($key, $startDate, $endDate);
        return $this;
    }

    public function equals($key, $value)
    {
        $this->queryConditions[] = new EqualsCondition($key, $value);
        return $this;
    }

    public function notEquals($key, $value)
    {
        $this->queryConditions[] = new NotEqualsCondition($key, $value);
        return $this;
    }

    public function like($key, $value)
    {
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

        $this->queryConditions[] = new InCondition($key, $values);
        return $this;
    }

    public function isNull($key)
    {
        $this->queryConditions[] = new NullCondition($key);
        return $this;
    }

    public function isNotNull($key)
    {
        $this->queryConditions[] = new NotNullCondition($key);
        return $this;
    }

    private function addGroupConditions(Closure $closure, $type)
    {
        $queryParameters = new self();
        $queryConditionGroup = new QueryConditionGroup($type);
        $closure($queryParameters);

        $queryConditionGroup->addConditions($queryParameters->getQueryConditions());
        $this->queryConditions[] = $queryConditionGroup;
    }

}