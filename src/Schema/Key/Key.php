<?php

namespace Intersect\Database\Schema\Key;

class Key {

    protected $prefix = 'key_';

    private $name;
    private $columns;

    public function __construct($columns, $name = null)
    {
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

    private function generateName(array $columns)
    {
        return $this->prefix . implode('_', $columns);
    }

}