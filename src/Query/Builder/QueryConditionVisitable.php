<?php

namespace Intersect\Database\Query\Builder;

interface QueryConditionVisitable {

    public function accept(QueryConditionVisitor $queryConditionVisitor);
    
}