<?php

namespace Tests\Model;

use Tests\Stubs\Name;
use Tests\Stubs\User;
use Tests\Stubs\Phone;
use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\Connection;

class ModelRelationshipTest extends TestCase {

    public function test_userRelationships_lazy_hasOne()
    {
        $user = User::findOne();
        $name = $user->name;

        $this->assertNotNull($name);
        $this->assertInstanceOf(Name::class, $name);
        $this->assertEquals('Unit', $name->first_name);
        $this->assertEquals('Test', $name->last_name);
    }

    public function test_userRelationships_lazy_hasMany()
    {
        $user = User::findOne();
        $addresses = $user->addresses;
        
        $this->assertNotNull($addresses);
        $this->assertIsArray($addresses);
        $this->assertCount(2, $addresses);
    }

    public function test_userRelationships_eager()
    {
        $user = User::findOne();
        $phone = $user->phone;
        
        $this->assertNotNull($phone);
        $this->assertInstanceOf(Phone::class, $phone);
        $this->assertEquals('15551234567', $phone->number);
    }

}