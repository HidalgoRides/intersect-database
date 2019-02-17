<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\Condition\QueryCondition;

class BetweenDatesCondition extends QueryCondition {

    public function __construct($column, $startDate, $endDate)
    {
        parent::__construct($column, 'between dates', [$startDate, $endDate]);
    }

}