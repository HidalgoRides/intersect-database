<?php

namespace Tests\Stubs;

use Intersect\Database\Model\Model;

class TestModel extends Model {

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

}