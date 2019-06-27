<?php

namespace Intersect\Database\Schema\Resolver;

use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Resolver\AbstractColumnDefinitionResolver;

class PostgresColumnDefinitionResolver extends AbstractColumnDefinitionResolver {

    public function resolve(ColumnDefinition $columnDefinition)
    {
        if ($columnDefinition->isPrimary())
        {
            $columnDefinition->setType('serial')->length(null)->nullable(true)->autoIncrement(false);
        }

        return parent::resolve($columnDefinition);
    }

    protected function getUnsigned() {}

    protected function getType(ColumnDefinition $columnDefinition)
    {
        $type = $columnDefinition->getType();

        switch ($type)
        {
            case ColumnType::STRING:
                $type = 'varchar';
                break;
            case ColumnType::DATETIME:
                $type = 'timestamp';
                break;
            case ColumnType::TINY_INT:
            case ColumnType::MEDIUM_INT:
                $type = 'integer';
                break;
            case ColumnType::MEDIUM_TEXT:
            case ColumnType::LONG_TEXT:
                $type = 'text';
                break;
        }

        return $type;
    }

}