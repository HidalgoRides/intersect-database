<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\ResolvedQueryCondition;
use Intersect\Database\Query\Builder\Condition\QueryCondition;

class LikeCondition extends QueryCondition {

    public function __construct($column, $value)
    {
        parent::__construct($column, $value);
    }

    public function resolve(Query $query, $alias = null)
    {
        $column = $this->getColumn();
        $columnWithAlias = $this->buildColumnWithAlias($column, $alias);
        $placeholder = $this->buildPlaceholderWithAlias($query, $column, $alias);
        $queryString = $columnWithAlias . ' like :' . $placeholder;
        $bindParameters = [$placeholder, $this->getValue()];

        return new ResolvedQueryCondition($queryString, $bindParameters);
    }

}