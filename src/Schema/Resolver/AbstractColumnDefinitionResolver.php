<?php

namespace Intersect\Database\Schema\Resolver;

use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Resolver\ColumnDefinitionResolver;

abstract class AbstractColumnDefinitionResolver implements ColumnDefinitionResolver {

    private static $SUPPORTED_UNSIGNED_TYPES = [ColumnType::INTEGER, ColumnType::TINY_INT, ColumnType::SMALL_INT, ColumnType::MEDIUM_INT, ColumnType::BIG_INT];

    /** @var ColumnDefinition */
    protected $columnDefinition;

    public function resolve(ColumnDefinition $columnDefinition)
    {
        $this->columnDefinition = $columnDefinition;
        return $this->wrapColumnName($this->columnDefinition->getName()) . ' ' . $this->getType($this->columnDefinition) . $this->getLength() . $this->getNotNull() . $this->getAutoIncrement() . $this->getUnsigned() . $this->getDefaultValue();
    }

    abstract protected function getType(ColumnDefinition $columnDefinition);

    protected function getLength()
    {
        if ($this->columnDefinition->getType() == ColumnType::NUMERIC)
        {
            $length = '(' . $this->columnDefinition->getPrecision() . ',' . $this->columnDefinition->getScale() . ')';
        }
        else
        {
            $length = (!is_null($this->columnDefinition->getLength()) ? '(' . $this->columnDefinition->getLength() . ')' : '');
        }

        return $length;
    }

    protected function getNotNull()
    {
        return (!$this->columnDefinition->isNullable() ? ' not null' : '');
    }

    protected function getAutoIncrement()
    {
        return ($this->columnDefinition->isAutoIncrement() ? ' auto_increment' : '');
    }

    protected function getUnsigned()
    {
        return ($this->columnDefinition->isUnsigned() && (in_array($this->columnDefinition->getType(), self::$SUPPORTED_UNSIGNED_TYPES)) ? ' unsigned' : '');
    }

    protected function getDefaultValue()
    {
        return (!is_null($this->columnDefinition->getDefaultValue()) ? ' default \'' . $this->columnDefinition->getDefaultValue() . '\'' : '');
    }

    protected function wrapColumnName($columnName)
    {
        return $columnName;
    }

}