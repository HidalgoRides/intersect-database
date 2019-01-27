<?php

namespace Tests\Query;

use Intersect\Database\Query\AliasFactory;
use PHPUnit\Framework\TestCase;

class AliasFactoryTest extends TestCase {

    public function test_getKey()
    {
        $value1 = 'value1';
        $value2 = 'value2';

        $alias1 = AliasFactory::getAlias($value1);
        $alias2 = AliasFactory::getAlias($value2);
        $alias3 = AliasFactory::getAlias($value1);

        $this->assertNotEquals($alias1, $alias2);
        $this->assertEquals($alias1, $alias3);
    }

    public function test_getAliasValue()
    {
        $alias = AliasFactory::getAlias('test');

        $this->assertEquals('test', AliasFactory::getAliasValue($alias));
    }

}