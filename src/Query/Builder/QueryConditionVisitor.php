<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Query\Builder\Condition\QueryConditionGroup;

class QueryConditionVisitor {

    private $alias;
    private $query;

    public function __construct(Query $query, $alias = null)
    {
        $this->query = $query;
        $this->alias = $alias;
    }

    public function visitQueryCondition(QueryCondition $queryCondition)
    {   
        $resolvedQueryCondition = $queryCondition->resolve($this->query, $this->alias);

        $this->query->addQueryCondition($resolvedQueryCondition);

        $queryConditionString = $resolvedQueryCondition->getQueryString();

        $this->query->appendSql($queryConditionString);

        $parameters = $resolvedQueryCondition->getBindParameters();
        if (count($parameters) > 0) 
        {
            $this->query->bindParameter($parameters[0], $parameters[1]);
        }
    }

    public function visitQueryConditionGroup(QueryConditionGroup $queryConditionGroup)
    {
        $type = $queryConditionGroup->getType();
        $conditions = $queryConditionGroup->getConditions();
        $conditionsCount = count($conditions);

        if ($conditionsCount > 1) 
        {
            $this->query->appendSql('(');
        }

        for ($i = 0; $i < $conditionsCount; $i++) 
        {
            $queryCondition = $conditions[$i];

            if ($i > 0 && $i < ($conditionsCount)) 
            {
                $this->query->appendSql(' ' . $type . ' ');
            }

            $queryCondition->accept($this);
        }

        if ($conditionsCount > 1) 
        {
            $this->query->appendSql(')');
        }
    }

}