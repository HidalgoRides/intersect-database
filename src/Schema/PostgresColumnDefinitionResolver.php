<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\ColumnDefinitionResolver;

class PostgresColumnDefinitionResolver implements ColumnDefinitionResolver {
    
    public function resolve(ColumnDefinition $columnDefinition)
    {
        $type = $this->getType($columnDefinition->getType());
        $length = (!is_null($columnDefinition->getLength()) ? '(' . $columnDefinition->getLength() . ')' : '');
        $notNullable = (!$columnDefinition->isNullable() ? ' not null' : '');
        $autoIncrement = ($columnDefinition->isAutoIncrement() ? ' auto_increment' : '');
        $defaultValue = (!is_null($columnDefinition->getDefaultValue()) ? ' default \'' . $columnDefinition->getDefaultValue() . '\'' : '');

        if ($columnDefinition->isPrimary())
        {
            $type = 'serial';
            $length = null;
            $notNullable = null;
            $autoIncrement = null;
        }

        if ($type == 'numeric')
        {
            $type = $type . '(' . $columnDefinition->getPrecision() . ',' . $columnDefinition->getScale() . ')';
        }

        return $columnDefinition->getName() . ' ' . $type . $length . $notNullable . $autoIncrement . $defaultValue;
    }

    private function getType($columnDefinitionType)
    {
        $type = null;

        switch ($columnDefinitionType)
        {
            case 'string':
                $type = 'varchar';
                break;
            case 'datetime':
                $type = 'timestamp';
                break;
            default:
                $type = $columnDefinitionType;
        }

        return $type;
    }

}