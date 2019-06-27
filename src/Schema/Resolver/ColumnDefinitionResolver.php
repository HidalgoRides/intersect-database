<?php

namespace Intersect\Database\Schema\Resolver;

use Intersect\Database\Schema\ColumnDefinition;

interface ColumnDefinitionResolver {
    
    public function resolve(ColumnDefinition $columnDefinition);
    
}