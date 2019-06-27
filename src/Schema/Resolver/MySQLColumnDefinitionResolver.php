<?php

namespace Intersect\Database\Schema\Resolver;

use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Resolver\AbstractColumnDefinitionResolver;

class MySQLColumnDefinitionResolver extends AbstractColumnDefinitionResolver {

    protected function getType(ColumnDefinition $columnDefinition)
    {
        $type = $columnDefinition->getType();

        switch ($type)
        {
            case ColumnType::INTEGER:
                $type = 'int';
                break;
            case ColumnType::STRING:
                $type = 'varchar';
                break;
            case ColumnType::NUMERIC:
                $type = 'decimal';
                break;
        }

        return $type;
    }

    protected function wrapColumnName($columnName)
    {
        return '`' . $columnName . '`';
    }

}