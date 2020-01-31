<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\ResolvedQueryCondition;
use Intersect\Database\Query\Builder\Condition\QueryCondition;

class BetweenDatesCondition extends QueryCondition {

    public function __construct($column, $startDate, $endDate)
    {
        parent::__construct($column, [$startDate, $endDate]);
    }

    public function resolve(Query $query, $alias = null)
    {
        $values = $this->getValue();
        $columnWithAlias = $this->buildColumnWithAlias($this->getColumn(), $alias);
        return new ResolvedQueryCondition($columnWithAlias . ' between cast(\'' . $values[0] . '\' as datetime) and cast(\'' . $values[1] . '\' as datetime)');
    }

}