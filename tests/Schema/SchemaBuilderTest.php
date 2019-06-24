<?php

namespace Tests\Schema;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Query\Result;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Schema\SchemaBuilder;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\Builder\QueryBuilder;

class SchemaBuilderTest extends TestCase {

    /** @var Connection */
    private $connectionMock;

    /** @var QueryBuilder */
    private $queryBuilderMock;

    protected function setUp()
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);

        $this->connectionMock->method('getQueryBuilder')->willReturn($this->queryBuilderMock);
        
        SchemaBuilder::setConnection($this->connectionMock);
    }

    public function test_createTable()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('createTable')->willReturnCallback(function(Blueprint $b) use ($queryBuilder) {
            $this->assertEquals('test', $b->getTableName());
            $this->assertCount(1, $b->getColumnDefinitions());
            return $queryBuilder;
        });
        
        $queryBuilder->method('get')->willReturn(new Result());

        SchemaBuilder::createTable('test', function(Blueprint $b) {
            $b->string('unit');
        });
    }

    public function test_dropTable()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('dropTable')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });
        
        $queryBuilder->method('get')->willReturn(new Result());

        SchemaBuilder::dropTable('test');
    }

    public function test_dropTableIfExists()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('dropTableIfExists')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });
        
        $queryBuilder->method('get')->willReturn(new Result());

        SchemaBuilder::dropTableIfExists('test');
    }

}