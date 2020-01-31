<?php

namespace Intersect\Database\Query\Builder\Condition;

use Intersect\Database\Query\Builder\QueryConditionVisitor;
use Intersect\Database\Query\Builder\QueryConditionVisitable;

class QueryConditionGroup implements QueryConditionVisitable {
    
    private $conditions = [];
    private $type = QueryConditionType::AND;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function addCondition(QueryConditionVisitable $queryCondition)
    {
        $this->conditions[] = $queryCondition;
    }

    public function addConditions(array $queryConditions)
    {
        foreach ($queryConditions as $queryCondition)
        {
            if ($queryCondition instanceof QueryConditionVisitable)
            {
                $this->addCondition($queryCondition);
            }
        }
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function getType()
    {
        return $this->type;
    }

    public function accept(QueryConditionVisitor $visitor) 
    {
        $visitor->visitQueryConditionGroup($this);
    }
}