<?php

use Intersect\Database\Migrations\AbstractSeed;
use Tests\Stubs\TestModel;

class TestSeed1561525934 extends AbstractSeed {

    public function populate()
    {
        $exportOne = new TestModel('test_export_one');
        $exportOne->email = 'unit@test.com';
        $exportOne->save();
    }

}