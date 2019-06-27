<?php

namespace Tests\Schema;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Resolver\MySQLColumnDefinitionResolver;

class MySQLColumnDefinitionResolverTest extends TestCase {

    /** @var MySQLColumnDefinitionResolver */
    private $resolver;

    protected function setUp()
    {
        $this->resolver = new MySQLColumnDefinitionResolver();
    }

    public function test_resolve_stringType()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');

        $result = $this->resolver->resolve($columnDefinition);

        $this->assertEquals('`test` varchar not null', $result);
    }

    public function test_resolve_datetimeType()
    {
        $columnDefinition = new ColumnDefinition('test', 'datetime');

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`test` datetime not null', $result);
    }

    public function test_resolve_defaultType()
    {
        $columnDefinition = new ColumnDefinition('test', 'integer');

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`test` int not null', $result);
    }

    public function test_resolve_primary()
    {
        $columnDefinition = new ColumnDefinition('id', 'integer');
        $columnDefinition->primary();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`id` int not null', $result);
    }

    public function test_resolve_numericType()
    {
        $columnDefinition = new ColumnDefinition('price', 'numeric');
        $columnDefinition->precision(4);
        $columnDefinition->scale(2);

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`price` decimal(4,2) not null', $result);
    }

    public function test_resolve_nullable()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');
        $columnDefinition->nullable();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`test` varchar', $result);
    }

    public function test_resolve_autoIncrement()
    {
        $columnDefinition = new ColumnDefinition('test', 'integer');
        $columnDefinition->autoIncrement();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`test` int not null auto_increment', $result);
    }

    public function test_resolve_length()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');
        $columnDefinition->length(100);

        $result = $this->resolver->resolve($columnDefinition);

        $this->assertEquals('`test` varchar(100) not null', $result);
    }

    public function test_resolve_defaultValue()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');
        $columnDefinition->default('unit');

        $result = $this->resolver->resolve($columnDefinition);

        $this->assertEquals('`test` varchar not null default \'unit\'', $result);
    }

    public function test_resolve_unsigned()
    {
        $columnDefinition = new ColumnDefinition('test', 'integer');
        $columnDefinition->unsigned();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('`test` int not null unsigned', $result);
    }

}