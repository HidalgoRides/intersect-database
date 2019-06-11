<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\ColumnDefinitionResolver;

class NullColumnDefinitionResolver implements ColumnDefinitionResolver {
    
    public function resolve(ColumnDefinition $columnDefinition)
    {
        return null;
    }

}