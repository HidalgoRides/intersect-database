<?php

namespace Tests\Model;

use Tests\Stubs\Name;
use Tests\Stubs\User;
use Tests\Stubs\Phone;
use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\Connection;

class ModelRelationshipTest extends TestCase {

    public function test_userRelationships()
    {
        $user = User::findOne();
        $name = $user->name;
        $phone = $user->phone;

        $this->assertNotNull($name);
        $this->assertNotNull($phone);
        $this->assertTrue($name instanceof Name);
        $this->assertTrue($phone instanceof Phone);
        $this->assertEquals('Unit', $name->first_name);
        $this->assertEquals('Test', $name->last_name);
        $this->assertEquals('15551234567', $phone->number);
    }

}