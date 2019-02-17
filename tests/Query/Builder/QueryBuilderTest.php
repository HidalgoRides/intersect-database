<?php

namespace Tests\Query\Builder;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Query\Builder\QueryBuilder;

class QueryBuilderTest extends TestCase {

    public function test_buildSelectQuery_allColumns()
    {
        $query = QueryBuilder::select()
            ->table('users')
            ->build();

        $this->assertEquals("select * from `users`", $query->getSql());
    }

    public function test_buildSelectQuery_specificColumns()
    {
        $query = QueryBuilder::select(['id', 'email'])
            ->table('users')
            ->build();

        $this->assertEquals("select id, email from `users`", $query->getSql());
    }

    public function test_buildSelectQuery_withWhereConditions()
    {
        $query = QueryBuilder::select()
            ->table('users')
            ->whereEquals('unit', 'test')
            ->whereNotEquals('test', 'unit')
            ->whereNull('foo')
            ->whereNotNull('bar')
            ->whereIn('id', [1,2,3])
            ->whereBetween('value', 1, 10)
            ->whereBetweenDates('start', '1969-01-01', '1969-01-02')
            ->whereLike('foo', 'test%')
            ->build();

        $this->assertEquals("select * from `users` where unit = :unit and test != :test and foo is null and bar is not null and id in (1, 2, 3) and value between 1 and 10 and start between cast('1969-01-01' as datetime) and cast('1969-01-02' as datetime) and foo like :foo", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('unit', $bindParameters);
        $this->assertArrayHasKey('test', $bindParameters);
        $this->assertArrayHasKey('foo', $bindParameters);
        $this->assertEquals('test', $bindParameters['unit']);
        $this->assertEquals('unit', $bindParameters['test']);
        $this->assertEquals('test%', $bindParameters['foo']);
    }

    public function test_buildSelectQuery_withLimit()
    {
        $query = QueryBuilder::select()
            ->table('users')
            ->limit(3)
            ->build();

        $this->assertEquals("select * from `users` limit 3", $query->getSql());
    }

    public function test_buildSelectQuery_withOrder()
    {
        $query = QueryBuilder::select()
            ->table('users')
            ->orderBy('id', 'desc')
            ->build();

        $this->assertEquals("select * from `users` order by id desc", $query->getSql());
    }

    public function test_buildDeleteQuery()
    {
        $query = QueryBuilder::delete()
            ->table('users')
            ->build();

        $this->assertEquals("delete from `users`", $query->getSql());
    }

    public function test_buildDeleteQuery_withOrderAndLimit()
    {
        $query = QueryBuilder::delete()
            ->table('users')
            ->orderBy('id')
            ->limit(2)
            ->build();

        $this->assertEquals("delete from `users` order by id asc limit 2", $query->getSql());
    }

    public function test_buildDeleteQuery_withWhereConditions()
    {
        $query = QueryBuilder::delete()
            ->table('users')
            ->whereIn('id', [1,2,3])
            ->build();

        $this->assertEquals("delete from `users` where id in (1, 2, 3)", $query->getSql());
    }

    public function test_buildUpdateQuery_withWhereConditions()
    {
        $columnData = [
            'email' => 'unit-test@test.com'
        ];

        $query = QueryBuilder::update($columnData)
            ->table('users')
            ->whereEquals('id', 1)
            ->build();

        $this->assertEquals("update `users` set email = :email where id = :id", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('id', $bindParameters);
        $this->assertEquals(1, $bindParameters['id']);
    }
    
}