<?php

namespace Intersect\Database\Schema;

class Column {

    private $name;
    private $type;
    private $defaultValue;
    private $allowsNull;
    private $autoIncrement;

    public function __construct($name, $type, $allowsNull = false, $defaultValue = null, $autoIncrement = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->allowsNull = $allowsNull;
        $this->defaultValue = $defaultValue;
        $this->autoIncrement = $autoIncrement;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    public function getAllowsNull()
    {
        return $this->allowsNull;
    }

    public function setAllowsNull(bool $allowsNull)
    {
        $this->allowsNull = $allowsNull;
    }

    public function getIsAutoIncrement()
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(bool $autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

}