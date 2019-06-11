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
        $primaryKey = ($columnDefinition->isPrimary() ? ' primary key' : '');
        $unique = ($columnDefinition->isUnique() ? ' unique' : '');

        if ($columnDefinition->isPrimary())
        {
            $type = 'serial';
            $length = null;
            $notNullable = null;
            $autoIncrement = null;
        }

        return $columnDefinition->getName() . ' ' . $type . $length . $notNullable . $autoIncrement . $primaryKey . $unique;
    }

    private function getType($columnDefinitionType)
    {
        $type = null;

        switch ($columnDefinitionType)
        {
            case 'string':
                $type = 'varchar';
                break;
            default:
                $type = $columnDefinitionType;
        }

        return $type;
    }

}