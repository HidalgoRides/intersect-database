<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\Condition\QueryCondition;

class BetweenCondition extends QueryCondition {

    public function __construct($column, $startValue, $endValue)
    {
        parent::__construct($column, 'between', [$startValue, $endValue]);
    }

}