<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\ResolvedQueryCondition;
use Intersect\Database\Query\Builder\Condition\QueryCondition;

class NullCondition extends QueryCondition {

    public function __construct($column)
    {
        parent::__construct($column);
    }

    public function resolve(Query $query, $alias = null)
    {
        $columnWithAlias = $this->buildColumnWithAlias($this->getColumn(), $alias);
        
        return new ResolvedQueryCondition($columnWithAlias . ' is null');
    }

}