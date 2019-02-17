<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\Condition\QueryCondition;

class NullCondition extends QueryCondition {

    public function __construct($column)
    {
        parent::__construct($column, 'is null');
    }

}