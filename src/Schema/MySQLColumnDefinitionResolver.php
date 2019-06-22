<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\ColumnDefinitionResolver;

class MySQLColumnDefinitionResolver implements ColumnDefinitionResolver {
    
    public function resolve(ColumnDefinition $columnDefinition)
    {
        $type = $this->getType($columnDefinition->getType());
        $length = (!is_null($columnDefinition->getLength()) ? '(' . $columnDefinition->getLength() . ')' : '');
        $notNullable = (!$columnDefinition->isNullable() ? ' not null' : '');
        $autoIncrement = ($columnDefinition->isAutoIncrement() ? ' auto_increment' : '');
        $defaultValue = (!is_null($columnDefinition->getDefaultValue()) ? ' default \'' . $columnDefinition->getDefaultValue() . '\'' : '');

        if ($type == 'decimal')
        {
            $type = $type . '(' . $columnDefinition->getPrecision() . ',' . $columnDefinition->getScale() . ')';
        }

        $unsigned = ((in_array($type, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint']) && $columnDefinition->isUnsigned()) ? ' unsigned' : '');

        return '`' . $columnDefinition->getName() . '` ' . $type . $length . $notNullable . $autoIncrement . $unsigned . $defaultValue;
    }

    private function getType($columnDefinitionType)
    {
        $type = null;

        switch ($columnDefinitionType)
        {
            case 'integer':
                $type = 'int';
                break;
            case 'string':
                $type = 'varchar';
                break;
            case 'numeric':
                $type = 'decimal';
                break;
            default:
                $type = $columnDefinitionType;
        }

        return $type;
    }

}