<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\Condition\QueryCondition;

class NotEqualsCondition extends QueryCondition {

    public function __construct($column, $value)
    {
        parent::__construct($column, '!=', $value);
    }

}