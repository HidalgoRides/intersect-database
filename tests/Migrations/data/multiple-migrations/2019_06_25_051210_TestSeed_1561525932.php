<?php

use Intersect\Database\Migrations\AbstractSeed;
use Tests\Stubs\Seed;

class TestSeed1561525932 extends AbstractSeed {

    public function populate()
    {
        $seed = new Seed();
        $seed->email = 'test_' . time() . '@test.com';
        $seed->save();
    }

}