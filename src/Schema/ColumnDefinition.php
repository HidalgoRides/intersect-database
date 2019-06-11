<?php

namespace Intersect\Database\Schema;

class ColumnDefinition {

    private $isAutoIncrement = false;
    private $length;
    private $name;
    private $isNullable = false;
    private $isPrimary = false;
    private $isUnique = false;
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

    public function isUnique()
    {
        return $this->isUnique;
    }

    public function unique()
    {
        $this->isUnique = true;
        return $this;
    }

}