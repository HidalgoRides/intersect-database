<?php

namespace Tests\Schema;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\PostgresColumnDefinitionResolver;
use Intersect\Database\Schema\ColumnDefinition;

class PostgresColumnDefinitionResolverTest extends TestCase {

    /** @var PostgresColumnDefinitionResolver */
    private $resolver;

    protected function setUp()
    {
        $this->resolver = new PostgresColumnDefinitionResolver();
    }

    public function test_resolve_stringType()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');

        $result = $this->resolver->resolve($columnDefinition);

        $this->assertEquals('test varchar not null', $result);
    }

    public function test_resolve_datetimeType()
    {
        $columnDefinition = new ColumnDefinition('test', 'datetime');

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('test timestamp not null', $result);
    }

    public function test_resolve_defaultType()
    {
        $columnDefinition = new ColumnDefinition('test', 'integer');

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('test integer not null', $result);
    }

    public function test_resolve_primary()
    {
        $columnDefinition = new ColumnDefinition('id', 'integer');
        $columnDefinition->primary();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('id serial', $result);
    }

    public function test_resolve_numericType()
    {
        $columnDefinition = new ColumnDefinition('price', 'numeric');
        $columnDefinition->precision(4);
        $columnDefinition->scale(2);

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('price numeric(4,2) not null', $result);
    }

    public function test_resolve_nullable()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');
        $columnDefinition->nullable();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('test varchar', $result);
    }

    public function test_resolve_autoIncrement()
    {
        $columnDefinition = new ColumnDefinition('test', 'integer');
        $columnDefinition->autoIncrement();

        $result = $this->resolver->resolve($columnDefinition);
        $this->assertEquals('test integer not null auto_increment', $result);
    }

    public function test_resolve_length()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');
        $columnDefinition->length(100);

        $result = $this->resolver->resolve($columnDefinition);

        $this->assertEquals('test varchar(100) not null', $result);
    }

    public function test_resolve_defaultValue()
    {
        $columnDefinition = new ColumnDefinition('test', 'string');
        $columnDefinition->default('unit');

        $result = $this->resolver->resolve($columnDefinition);

        $this->assertEquals('test varchar not null default \'unit\'', $result);
    }

}