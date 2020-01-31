<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\ResolvedQueryCondition;
use Intersect\Database\Query\Builder\Condition\QueryCondition;

class BetweenCondition extends QueryCondition {

    public function __construct($column, $startValue, $endValue)
    {
        parent::__construct($column, [$startValue, $endValue]);
    }

    public function resolve(Query $query, $alias = null)
    {
        $values = $this->getValue();
        $columnWithAlias = $this->buildColumnWithAlias($this->getColumn(), $alias);
        return new ResolvedQueryCondition($columnWithAlias . ' between ' . $values[0] . ' and ' . $values[1]);
    }

}