<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Query\Builder\Condition\EqualsCondition;
use Intersect\Database\Query\Builder\Condition\NotEqualsCondition;
use Intersect\Database\Query\Builder\Condition\NotNullCondition;
use Intersect\Database\Query\Builder\Condition\NullCondition;
use Intersect\Database\Query\Builder\Condition\InCondition;
use Intersect\Database\Query\Builder\Condition\BetweenCondition;
use Intersect\Database\Query\Builder\Condition\BetweenDatesCondition;
use Intersect\Database\Query\Builder\Condition\LikeCondition;

class QueryConditionResolver {

    /** @return ResolvedQueryCondition */
    public function resolve(QueryCondition $queryCondition, $alias = null)
    {
        $queryString = '';
        $bindParameters = null;

        $column = $queryCondition->getColumn();
        $columnWithAlias = $this->buildColumnWithAlias($queryCondition->getColumn(), $alias);

        if ($queryCondition instanceof EqualsCondition)
        {
            $placeholder = $this->buildPlaceholderWithAlias($column, $alias);
            $queryString = $columnWithAlias . ' = :' . $placeholder;
            $bindParameters = [$placeholder, $queryCondition->getValue()];
        }
        else if ($queryCondition instanceof NotEqualsCondition)
        {
            $placeholder = $this->buildPlaceholderWithAlias($column, $alias);
            $queryString = $columnWithAlias . ' != :' . $placeholder;
            $bindParameters = [$placeholder, $queryCondition->getValue()];
        }
        else if ($queryCondition instanceof NullCondition)
        {
            $queryString = $columnWithAlias . ' is null';
        }
        else if ($queryCondition instanceof NotNullCondition)
        {
            $queryString = $columnWithAlias . ' is not null';
        }
        else if ($queryCondition instanceof LikeCondition)
        {
            $placeholder = $this->buildPlaceholderWithAlias($column, $alias);
            $queryString = $columnWithAlias . ' like :' . $placeholder;
            $bindParameters = [$placeholder, $queryCondition->getValue()];
        }
        else if ($queryCondition instanceof InCondition)
        {
            $queryString = $columnWithAlias . ' in (' . implode(', ', $queryCondition->getValue()) . ')';
        }
        else if ($queryCondition instanceof BetweenCondition)
        {
            $values = $queryCondition->getValue();
            $queryString = $columnWithAlias . ' between ' . $values[0] . ' and ' . $values[1];
        }
        else if ($queryCondition instanceof BetweenDatesCondition)
        {
            $values = $queryCondition->getValue();
            $queryString = $columnWithAlias . ' between cast(\'' . $values[0] . '\' as datetime) and cast(\'' . $values[1] . '\' as datetime)';
        }

        return new ResolvedQueryCondition($queryString, $bindParameters);
    }

    private function buildColumnWithAlias($column, $alias)
    {
        return (!is_null($alias)) ? ($alias . '.' . $column) : $column;
    }

    private function buildPlaceholderWithAlias($key, $alias)
    {
        return (!is_null($alias)) ? ($alias . '_'. $key) : $key;
    }

}

class ResolvedQueryCondition {

    private $queryString;
    private $bindParameters;

    public function __construct($queryString, array $bindParameters = null)
    {
        $this->queryString = $queryString;
        $this->bindParameters = $bindParameters;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }

    public function getBindParameters()
    {
        return $this->bindParameters;
    }

}