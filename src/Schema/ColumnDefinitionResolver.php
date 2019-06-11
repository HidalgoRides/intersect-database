<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnDefinition;

interface ColumnDefinitionResolver {
    
    public function resolve(ColumnDefinition $columnDefinition);
    
}