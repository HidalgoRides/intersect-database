<?php

namespace Tests\Schema;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Query\Result;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Schema\Schema;
use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Schema\ColumnBlueprint;

class SchemaTest extends TestCase {

    /** @var Connection */
    private $connectionMock;

    /** @var QueryBuilder */
    private $queryBuilderMock;

    protected function setUp()
    {
        $this->connectionMock = $this->createMock(Connection::class);
        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);

        $this->connectionMock->method('getQueryBuilder')->willReturn($this->queryBuilderMock);
    }

    public function test_createTable()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('createTable')->willReturnCallback(function(Blueprint $b) use ($queryBuilder) {
            $this->assertEquals('test', $b->getTableName());
            $this->assertCount(1, $b->getColumnDefinitions());
            return $queryBuilder;
        });
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->createTable('test', function(Blueprint $b) {
            $b->string('unit');
        });

        $this->assertEquals($expectedResult, $result);
    }

    public function test_createTableIfNotExists()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('createTable')->willReturnCallback(function(Blueprint $b) use ($queryBuilder) {
            $this->assertEquals('test', $b->getTableName());
            $this->assertCount(1, $b->getColumnDefinitions());
            return $queryBuilder;
        });
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->createTableIfNotExists('test', function(Blueprint $b) {
            $b->string('unit');
        });

        $this->assertEquals($expectedResult, $result);
    }

    public function test_dropTable()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('dropTable')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->dropTable('test');

        $this->assertEquals($expectedResult, $result);
    }

    public function test_dropTableIfExists()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('dropTableIfExists')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->dropTableIfExists('test');

        $this->assertEquals($expectedResult, $result);
    }

    public function test_dropColumns()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('table')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });

        $queryBuilder->method('dropColumns')->willReturn($queryBuilder);
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->dropColumns('test', ['email']);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_addColumn()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('table')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });

        $queryBuilder->method('addColumn')->willReturn($queryBuilder);
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);

        $columnBlueprint = new ColumnBlueprint();
        $columnBlueprint->string('email');

        $result = $schema->addColumn('test', $columnBlueprint);

        $this->assertEquals($expectedResult, $result);
    }

    public function test_createIndex()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('table')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });

        $queryBuilder->method('createIndex')->willReturn($queryBuilder);
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->createIndex('test', ['id'], 'index_name');

        $this->assertEquals($expectedResult, $result);
    }

    public function test_dropIndex()
    {
        $queryBuilder = $this->queryBuilderMock;

        $queryBuilder->method('table')->willReturnCallback(function($tableName) use ($queryBuilder) {
            $this->assertEquals('test', $tableName);
            return $queryBuilder;
        });

        $queryBuilder->method('dropIndex')->willReturn($queryBuilder);
        
        $expectedResult = new Result();
        $queryBuilder->method('get')->willReturn($expectedResult);

        $schema = new Schema($this->connectionMock);
        $result = $schema->dropIndex('test', 'index_name');

        $this->assertEquals($expectedResult, $result);
    }

}