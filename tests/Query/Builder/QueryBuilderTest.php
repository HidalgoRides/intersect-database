<?php

namespace Tests\Query\Builder;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Connection\NullConnection;

class QueryBuilderTest extends TestCase {

    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp()
    {
        $this->queryBuilder = new QueryBuilder(new NullConnection());
    }

    public function test_buildSelectQuery_allColumns()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', $alias)
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias, $query->getSql());
    }

    public function test_buildSelectQuery_specificColumns()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select(['id', 'email'])
            ->table('users', $alias)
            ->build();

        $this->assertEquals("select " . $alias . ".id as '" . $alias . ".id', " . $alias . ".email as '" . $alias . ".email' from `users` as " . $alias, $query->getSql());
    }

    public function test_buildSelectQuery_withWhereConditions()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', $alias)
            ->whereEquals('unit', 'test')
            ->whereNotEquals('test', 'unit')
            ->whereNull('foo')
            ->whereNotNull('bar')
            ->whereIn('id', [1,2,3])
            ->whereBetween('value', 1, 10)
            ->whereBetweenDates('start', '1969-01-01', '1969-01-02')
            ->whereLike('foo', 'test%')
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . " where " . $alias . ".unit = :" . $alias . "_unit and " . $alias . ".test != :" . $alias . "_test and " . $alias . ".foo is null and " . $alias . ".bar is not null and " . $alias . ".id in (1, 2, 3) and " . $alias . ".value between 1 and 10 and " . $alias . ".start between cast('1969-01-01' as datetime) and cast('1969-01-02' as datetime) and " . $alias . ".foo like :" . $alias . "_foo", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey($alias . '_unit', $bindParameters);
        $this->assertArrayHasKey($alias . '_test', $bindParameters);
        $this->assertArrayHasKey($alias . '_foo', $bindParameters);
        $this->assertEquals('test', $bindParameters[$alias . '_unit']);
        $this->assertEquals('unit', $bindParameters[$alias . '_test']);
        $this->assertEquals('test%', $bindParameters[$alias . '_foo']);
    }

    public function test_buildSelectQuery_withLimit()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', $alias)
            ->limit(3)
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . " limit 3", $query->getSql());
    }

    public function test_buildSelectQuery_withOrder()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', $alias)
            ->orderBy('id', 'desc')
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . " order by " . $alias . ".id desc", $query->getSql());
    }

    public function test_buildSelectQuery_withJoinLeft()
    {
        $usersAlias = 'a0';
        $phonesAlias = 'a1';
        $query = $this->queryBuilder->select()
            ->table('users', $usersAlias)
            ->joinLeft('phones', 'id', 'phone_id', [], $phonesAlias)
            ->build();

        $this->assertEquals("select " . $usersAlias . ".* as '" . $usersAlias . ".*' from `users` as " . $usersAlias . " left join `phones` as " . $phonesAlias . " on " . $usersAlias . ".phone_id = " . $phonesAlias . ".id", $query->getSql());
    }

    public function test_buildDeleteQuery()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->build();

        $this->assertEquals("delete from `users`", $query->getSql());
    }

    public function test_buildDeleteQuery_withOrderAndLimit()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->orderBy('id')
            ->limit(2)
            ->build();

        $this->assertEquals("delete from `users` order by id asc limit 2", $query->getSql());
    }

    public function test_buildDeleteQuery_withWhereConditions()
    {
        $query = $this->queryBuilder->delete()
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

        $query = $this->queryBuilder->update($columnData)
            ->table('users')
            ->whereEquals('id', 1)
            ->build();

        $this->assertEquals("update `users` set email = :email where id = :id", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('id', $bindParameters);
        $this->assertEquals(1, $bindParameters['id']);
    }
    
}