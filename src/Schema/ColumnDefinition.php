<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\Blueprint;

class ColumnDefinition {

    private $isAutoIncrement = false;
    private $length;
    private $name;
    private $precision;
    private $scale;
    private $defaultValue;
    private $isNullable = false;
    private $isPrimary = false;
    private $isUnsigned = false;
    private $type;

    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isAutoIncrement()
    {
        return $this->isAutoIncrement;
    }

    public function autoIncrement()
    {
        $this->isAutoIncrement = true;
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function default($value)
    {
        $this->defaultValue = $value;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function length($length)
    {
        $this->length = $length;
        return $this;
    }

    public function isNullable()
    {
        return $this->isNullable;
    }

    public function nullable()
    {
        $this->isNullable = true;
        return $this;
    }

    public function isPrimary()
    {
        return $this->isPrimary;
    }

    public function primary()
    {
        $this->isPrimary = true;
        return $this;
    }

    public function getPrecision()
    {
        return $this->precision;
    }

    public function precision($precision)
    {
        $this->precision = $precision;
        return $this;
    }

    public function getScale()
    {
        return $this->scale;
    }

    public function scale($scale)
    {
        $this->scale = $scale;
        return $this;
    }

    public function isUnsigned()
    {
        return $this->isUnsigned;
    }

    public function unsigned()
    {
        $this->isUnsigned = true;
        return $this;
    }

}