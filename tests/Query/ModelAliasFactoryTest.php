<?php

namespace Tests\Query;

use Tests\Stubs\Name;
use Tests\Stubs\User;
use Tests\Stubs\Phone;
use PHPUnit\Framework\TestCase;
use Intersect\Database\Query\ModelAliasFactory;

class ModelAliasFactoryTest extends TestCase {

    public function test_getKey()
    {
        $user = new User();
        $name = new Name();

        $alias1 = ModelAliasFactory::generateAlias($user);
        $alias2 = ModelAliasFactory::generateAlias($name);
        $alias3 = ModelAliasFactory::generateAlias($user);

        $this->assertNotEquals($alias1, $alias2);
        $this->assertEquals($alias1, $alias3);
    }

    public function test_getAliasValue()
    {
        $phone = new Phone();
        $alias = ModelAliasFactory::generateAlias($phone);

        $this->assertEquals(get_class($phone), ModelAliasFactory::getAliasValue($alias)->getModelClassName());
    }

}