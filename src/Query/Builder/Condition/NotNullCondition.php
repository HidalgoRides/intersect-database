<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\Condition\QueryCondition;

class NotNullCondition extends QueryCondition {

    public function __construct($column)
    {
        parent::__construct($column, 'is not null');
    }

}