<?php

namespace Tests\Query\Builder;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Connection\NullConnection;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Query\Builder\PostgresQueryBuilder;

class PostgresQueryBuilderTest extends TestCase {

    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp()
    {
        $this->queryBuilder = new PostgresQueryBuilder(new NullConnection());
    }

    public function test_buildColumnsQuery()
    {
        $query = $this->queryBuilder->columns()
            ->table('users')
            ->build();

        $this->assertEquals("select column_name as \"Field\" from information_schema.columns where table_schema = 'public' and table_name = 'users'", $query->getSql());
    }

    public function test_buildColumnsQuery_withSchema()
    {
        $query = $this->queryBuilder->columns()
            ->table('users')
            ->schema('test')
            ->build();

        $this->assertEquals("select column_name as \"Field\" from information_schema.columns where table_schema = 'test' and table_name = 'users'", $query->getSql());
    }

    public function test_buildCountQuery()
    {
        $query = $this->queryBuilder->count()
            ->table('users')
            ->build();

        $this->assertEquals("select count(*) as count from public.users", $query->getSql());
    }

    public function test_buildCountQuery_withSchema()
    {
        $query = $this->queryBuilder->count()
            ->table('users')
            ->schema('test')
            ->build();

        $this->assertEquals("select count(*) as count from test.users", $query->getSql());
    }

    public function test_buildCountQuery_withWhereConditions()
    {
        $query = $this->queryBuilder->count()
            ->table('users')
            ->whereEquals('unit', 'test')
            ->build();

        $this->assertEquals("select count(*) as count from public.users where unit = :unit", $query->getSql());
        
        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('unit', $bindParameters);
        $this->assertEquals('test', $bindParameters['unit']);
    }

    public function test_buildSelectQuery_allColumns()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $alias)
            ->build();

        $this->assertEquals("select " . $alias . ".* as \"" . $alias . ".*\" from public.users as " . $alias, $query->getSql());
    }

    public function test_buildSelectQuery_withSchema()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $alias)
            ->schema('test')
            ->build();

        $this->assertEquals("select " . $alias . ".* as \"" . $alias . ".*\" from test.users as " . $alias, $query->getSql());
    }

    public function test_buildSelectQuery_specificColumns()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select(['id', 'email'])
            ->table('users', 'id', $alias)
            ->build();

        $this->assertEquals("select " . $alias . ".id as \"" . $alias . ".id\", " . $alias . ".email as \"" . $alias . ".email\" from public.users as " . $alias, $query->getSql());
    }

    public function test_buildSelectQuery_withWhereConditions()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $alias)
            ->whereEquals('unit', 'test')
            ->whereNotEquals('test', 'unit')
            ->whereNull('foo')
            ->whereNotNull('bar')
            ->whereIn('id', [1,2,3])
            ->whereBetween('value', 1, 10)
            ->whereBetweenDates('start', '1969-01-01', '1969-01-02')
            ->whereLike('foo', 'test%')
            ->build();

        $this->assertEquals("select " . $alias . ".* as \"" . $alias . ".*\" from public.users as " . $alias . " where " . $alias . ".unit = :" . $alias . "_unit and " . $alias . ".test != :" . $alias . "_test and " . $alias . ".foo is null and " . $alias . ".bar is not null and " . $alias . ".id in (1, 2, 3) and " . $alias . ".value between 1 and 10 and " . $alias . ".start between cast('1969-01-01' as datetime) and cast('1969-01-02' as datetime) and " . $alias . ".foo like :" . $alias . "_foo", $query->getSql());

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
            ->table('users', 'id', $alias)
            ->limit(3)
            ->build();

        $this->assertEquals("select " . $alias . ".* as \"" . $alias . ".*\" from public.users as " . $alias . " limit 3", $query->getSql());
    }

    public function test_buildSelectQuery_withOrder()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $alias)
            ->orderBy('id', 'desc')
            ->build();

        $this->assertEquals("select " . $alias . ".* as \"" . $alias . ".*\" from public.users as " . $alias . " order by " . $alias . ".id desc", $query->getSql());
    }

    public function test_buildSelectQuery_withJoinLeft()
    {
        $usersAlias = 'a0';
        $phonesAlias = 'a1';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $usersAlias)
            ->joinLeft('phones', 'id', 'phone_id', [], $phonesAlias)
            ->build();

        $this->assertEquals("select " . $usersAlias . ".* as \"" . $usersAlias . ".*\" from public.users as " . $usersAlias . " left join public.phones as " . $phonesAlias . " on " . $usersAlias . ".phone_id = " . $phonesAlias . ".id", $query->getSql());
    }

    public function test_buildDeleteQuery()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->build();

        $this->assertEquals("delete from public.users", $query->getSql());
    }

    public function test_buildDeleteQuery_withSchema()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->schema('test')
            ->build();

        $this->assertEquals("delete from test.users", $query->getSql());
    }

    public function test_buildDeleteQuery_withOrderAndLimit()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->orderBy('id')
            ->limit(2)
            ->build();

        $this->assertEquals("delete from public.users where id in (select id from public.users order by id asc limit 2)", $query->getSql());
    }

    public function test_buildDeleteQuery_withWhereConditions()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->whereIn('id', [1,2,3])
            ->build();

        $this->assertEquals("delete from public.users where id in (1, 2, 3)", $query->getSql());
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

        $this->assertEquals("update public.users set email = :email where id = :id", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('id', $bindParameters);
        $this->assertEquals(1, $bindParameters['id']);
    }

    public function test_buildUpdateQuery_withOrderAndLimit()
    {
        $columnData = [
            'email' => 'unit-test@test.com'
        ];

        $query = $this->queryBuilder->update($columnData)
            ->table('users')
            ->orderBy('id')
            ->limit(5)
            ->build();

        $this->assertEquals("update public.users set email = :email where id in (select id from public.users order by id asc limit 5)", $query->getSql());
    }

    public function test_buildUpdateQuery_withWhereConditionsAndSchema()
    {
        $columnData = [
            'email' => 'unit-test@test.com'
        ];

        $query = $this->queryBuilder->update($columnData)
            ->table('users')
            ->schema('test')
            ->whereEquals('id', 1)
            ->build();

        $this->assertEquals("update test.users set email = :email where id = :id", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('id', $bindParameters);
        $this->assertEquals(1, $bindParameters['id']);
    }

    public function test_buildCreateTableQuery()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email', 100)->unique();
        $blueprint->integer('age')->nullable();
        $blueprint->text('bio')->nullable();
        $blueprint->timestamp('created_at');

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table public.users (id serial primary key, email varchar(100) not null unique, age integer, bio text, created_at timestamp not null)", $query->getSql());
    }

    public function test_buildCreateTableQuery_withSchema()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email', 100);
        $blueprint->integer('age')->nullable();
        $blueprint->text('bio')->nullable();
        $blueprint->timestamp('created_at');

        $query = $this->queryBuilder->createTable($blueprint)->schema('users')->build();

        $this->assertEquals("create table users.users (id serial primary key, email varchar(100) not null, age integer, bio text, created_at timestamp not null)", $query->getSql());
    }

    public function test_buildDropTableQuery()
    {
        $query = $this->queryBuilder->dropTable('users')->build();

        $this->assertEquals("drop table public.users", $query->getSql());
    }

    public function test_buildDropTableQuery_withSchema()
    {
        $query = $this->queryBuilder->dropTable('users')->schema('users')->build();

        $this->assertEquals("drop table users.users", $query->getSql());
    }
    
}