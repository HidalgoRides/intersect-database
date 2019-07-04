<?php

namespace Tests\Query\Builder;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Connection\NullConnection;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Query\Builder\PostgresQueryBuilder;
use Intersect\Database\Schema\ColumnBlueprint;

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
        $blueprint->string('email', 100);
        $blueprint->integer('age')->nullable()->default(2);
        $blueprint->smallInteger('sint')->nullable();
        $blueprint->bigInteger('bint')->nullable();
        $blueprint->numeric('price', 5, 2)->nullable();
        $blueprint->text('bio')->nullable();
        $blueprint->timestamp('created_at')->nullable();
        $blueprint->datetime('date_created')->nullable();

        $blueprint->unique('email');
        $blueprint->foreign('user_id', 'id', 'alt_users');

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table public.users (id serial, email varchar(100) not null, age integer default '2', sint smallint, bint bigint, price numeric(5,2), bio text, created_at timestamp, date_created timestamp, constraint primary_users_id primary key (id), constraint unique_users_email unique (email), constraint foreign_user_id_alt_users_id foreign key (user_id) references public.alt_users (id))", $query->getSql());
    }

    public function test_buildCreateTableQuery_withSchema()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email', 100);
        $blueprint->integer('age')->nullable()->default(2);
        $blueprint->smallInteger('sint')->nullable();
        $blueprint->bigInteger('bint')->nullable();
        $blueprint->numeric('price', 5, 2)->nullable();
        $blueprint->text('bio')->nullable();
        $blueprint->timestamp('created_at')->nullable();
        $blueprint->datetime('date_created')->nullable();

        $blueprint->unique('email');
        $blueprint->foreign('user_id', 'id', 'alt_users');

        $query = $this->queryBuilder->createTable($blueprint)->schema('users')->build();

        $this->assertEquals("create table users.users (id serial, email varchar(100) not null, age integer default '2', sint smallint, bint bigint, price numeric(5,2), bio text, created_at timestamp, date_created timestamp, constraint primary_users_id primary key (id), constraint unique_users_email unique (email), constraint foreign_user_id_alt_users_id foreign key (user_id) references public.alt_users (id))", $query->getSql());
    }

    public function test_buildCreateTableQuery_indexNotSupported()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');

        $blueprint->index('test');

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table public.users (id serial, constraint primary_users_id primary key (id))", $query->getSql());
    }

    public function test_buildCreateTableQuery_withMultipleUniqueKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('column_one');
        $blueprint->integer('column_two');

        $blueprint->unique(['column_one', 'column_two']);

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table public.users (column_one integer not null, column_two integer not null, constraint unique_users_column_one_column_two unique (column_one, column_two))", $query->getSql());
    }

    public function test_buildCreateTableQuery_withMultiplePrimaryKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('column_one');
        $blueprint->integer('column_two');

        $blueprint->primary(['column_one', 'column_two']);

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table public.users (column_one integer not null, column_two integer not null, constraint primary_users_column_one_column_two primary key (column_one, column_two))", $query->getSql());
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

    public function test_buildDropTableIfExistsQuery()
    {
        $query = $this->queryBuilder->dropTableIfExists('users')->build();

        $this->assertEquals("drop table if exists public.users", $query->getSql());
    }

    public function test_buildDropTableIfExistsQuery_withSchema()
    {
        $query = $this->queryBuilder->dropTableIfExists('users')->schema('users')->build();

        $this->assertEquals("drop table if exists users.users", $query->getSql());
    }

    public function test_buildSelectMaxQuery()
    {
        $query = $this->queryBuilder->table('users')->selectMax('score')->build();

        $this->assertEquals("select max(score) as max_value from public.users", $query->getSql());
    }

    public function test_buildSelectMaxQuery_withSchema()
    {
        $query = $this->queryBuilder->table('users')->schema('users')->selectMax('score')->build();

        $this->assertEquals("select max(score) as max_value from users.users", $query->getSql());
    }

    public function test_buildDropColumnsQuery()
    {
        $query = $this->queryBuilder->table('users')->dropColumns(['email'])->build();

        $this->assertEquals("alter table public.users drop column email", $query->getSql());
    }

    public function test_buildDropColumnsQuery_withSchema()
    {
        $query = $this->queryBuilder->table('users')->schema('users')->dropColumns(['email'])->build();

        $this->assertEquals("alter table users.users drop column email", $query->getSql());
    }

    public function test_buildDropColumnsQuery_withMultiple()
    {
        $query = $this->queryBuilder->table('users')->dropColumns(['email', 'name'])->build();

        $this->assertEquals("alter table public.users drop column email, drop column name", $query->getSql());
    }

    public function test_buildDropColumnsQuery_withMultiple_withSchema()
    {
        $query = $this->queryBuilder->table('users')->schema('users')->dropColumns(['email', 'name'])->build();

        $this->assertEquals("alter table users.users drop column email, drop column name", $query->getSql());
    }

    public function test_buildAddColumnQuery()
    {
        $columnBlueprint = new ColumnBlueprint();
        $columnBlueprint->string('email', 25);

        $query = $this->queryBuilder->table('users')->addColumn($columnBlueprint)->build();

        $this->assertEquals("alter table public.users add column email varchar(25) not null", $query->getSql());
    }

    public function test_buildAddColumnQuery_withSchema()
    {
        $columnBlueprint = new ColumnBlueprint();
        $columnBlueprint->string('email', 25);

        $query = $this->queryBuilder->table('users')->schema('users')->addColumn($columnBlueprint)->build();

        $this->assertEquals("alter table users.users add column email varchar(25) not null", $query->getSql());
    }

    public function test_buildDropIndexQuery()
    {
        $query = $this->queryBuilder->table('users')->dropIndex('index_name')->build();

        $this->assertEquals("drop index index_name", $query->getSql());
    }

    public function test_buildDropIndexQuery_withSchema()
    {
        $query = $this->queryBuilder->table('users')->schema('users')->dropIndex('index_name')->build();

        $this->assertEquals("drop index index_name", $query->getSql());
    }
    
}