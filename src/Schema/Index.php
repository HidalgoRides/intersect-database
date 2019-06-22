<?php

namespace Intersect\Database\Schema;

class Index {

    private $columns;

    public function __construct($columns)
    {
        if (!is_array($columns))
        {
            $columns = [$columns];
        }
        
        $this->columns = $columns;
    }

    public function getColumns()
    {
        return $this->columns;
    }

}