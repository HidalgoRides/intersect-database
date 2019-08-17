<?php

namespace Tests\Stubs;

use Intersect\Database\Model\Model;

class TestModel extends Model {

    public function __construct($tableName = null)
    {
        parent::__construct();

        if (!is_null($tableName))
        {
            $this->tableName = $tableName;
        }
    }

}