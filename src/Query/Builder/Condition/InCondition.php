<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\Condition\QueryCondition;

class InCondition extends QueryCondition {

    public function __construct($column, array $values)
    {
        parent::__construct($column, 'in', $values);
    }

}