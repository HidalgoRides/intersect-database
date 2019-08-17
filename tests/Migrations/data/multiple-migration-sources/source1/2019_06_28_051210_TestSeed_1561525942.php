<?php

use Intersect\Database\Migrations\AbstractSeed;
use Tests\Stubs\TestModel;

class TestSeed1561525942 extends AbstractSeed {

    public function populate()
    {
        $sourceOne = new TestModel('test_migration_source_2');
        $sourceOne->email = 'unit@test.com';
        $sourceOne->save();
    }

}