<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnDefinition;

class Blueprint {

    /** @var ColumnDefinition[] */
    private $columnDefinitions = [];

    private $tableName;

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /** @return ColumnDefinition[] */
    public function getColumnDefinitions()
    {
        return $this->columnDefinitions;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function increments($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'integer');
        $columnDefinition->primary();
        $columnDefinition->autoIncrement();

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function integer($name, int $length = null)
    {
        $columnDefinition = new ColumnDefinition($name, 'integer');
        $columnDefinition->length($length);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function string($name, $length = 255)
    {
        $columnDefinition = new ColumnDefinition($name, 'string');
        $columnDefinition->length($length);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function text($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'text');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function timestamp($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'timestamp');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    private function addColumnDefinition(ColumnDefinition $columnDefinition)
    {
        $this->columnDefinitions[] = $columnDefinition;
    }

}