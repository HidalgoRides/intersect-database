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
use Intersect\Database\Query\Builder\ResolvedQueryCondition;

class QueryConditionResolver {

    /** @return ResolvedQueryCondition */
    public function resolve(QueryCondition $queryCondition)
    {
        $queryString = '';
        $bindParameters = null;

        if ($queryCondition instanceOf EqualsCondition)
        {
            $column = $queryCondition->getColumn();
            $queryString = $column . ' = ' . $this->buildPlaceholder($column);
            $bindParameters = [$column, $queryCondition->getValue()];
        }
        else if ($queryCondition instanceOf NotEqualsCondition)
        {
            $column = $queryCondition->getColumn();
            $queryString = $column . ' != ' . $this->buildPlaceholder($column);
            $bindParameters = [$column, $queryCondition->getValue()];
        }
        else if ($queryCondition instanceOf NullCondition)
        {
            $queryString = $queryCondition->getColumn() . ' is null';
        }
        else if ($queryCondition instanceOf NotNullCondition)
        {
            $queryString = $queryCondition->getColumn() . ' is not null';
        }
        else if ($queryCondition instanceOf LikeCondition)
        {
            $column = $queryCondition->getColumn();
            $queryString = $column . ' like ' . $this->buildPlaceholder($column);
            $bindParameters = [$column, $queryCondition->getValue()];
        }
        else if ($queryCondition instanceOf InCondition)
        {
            $queryString = $queryCondition->getColumn() . ' in (' . implode(', ', $queryCondition->getValue()) . ')';
        }
        else if ($queryCondition instanceOf BetweenCondition)
        {
            $values = $queryCondition->getValue();
            $queryString = $queryCondition->getColumn() . ' between ' . $values[0] . ' and ' . $values[1];
        }
        else if ($queryCondition instanceOf BetweenDatesCondition)
        {
            $values = $queryCondition->getValue();
            $queryString = $queryCondition->getColumn() . ' between cast(\'' . $values[0] . '\' as datetime) and cast(\'' . $values[1] . '\' as datetime)';
        }

        return new ResolvedQueryCondition($queryString, $bindParameters);
    }

    private function buildPlaceholder($key)
    {
        return ':' . $key;
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