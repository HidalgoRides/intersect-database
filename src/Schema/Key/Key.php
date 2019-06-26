<?php

namespace Intersect\Database\Schema\Key;

class Key {

    protected $prefix = 'key_';

    private $columns;
    private $name;
    private $tableName;

    public function __construct($tableName, $columns, $name = null)
    {
        $this->tableName = $tableName;

        if (!is_array($columns))
        {
            $columns = [$columns];
        }

        if (is_null($name))
        {
            $name = $this->generateName($columns);
        }

        $this->columns = $columns;
        $this->name = $name;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    private function generateName(array $columns)
    {
        return $this->prefix . $this->tableName . '_' . implode('_', $columns);
    }

}