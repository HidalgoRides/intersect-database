<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\ResolvedQueryCondition;
use Intersect\Database\Query\Builder\Condition\QueryCondition;

class InCondition extends QueryCondition {

    public function __construct($column, array $values)
    {
        parent::__construct($column, $values);
    }

    public function resolve(Query $query, $alias = null)
    {
        $columnWithAlias = $this->buildColumnWithAlias($this->getColumn(), $alias);
        return new ResolvedQueryCondition($columnWithAlias . ' in (' . implode(', ', $this->getValue()) . ')');
    }

}