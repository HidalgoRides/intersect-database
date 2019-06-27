<?php

namespace Intersect\Database\Schema\Resolver;

use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Resolver\AbstractColumnDefinitionResolver;

class NullColumnDefinitionResolver extends AbstractColumnDefinitionResolver {
    
    public function resolve(ColumnDefinition $columnDefinition)
    {
        return null;
    }

}