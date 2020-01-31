<?php

namespace Tests\Query\Builder;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Schema\ColumnBlueprint;
use Intersect\Database\Connection\NullConnection;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Query\Builder\MySQLQueryBuilder;

class MySQLQueryBuilderTest extends TestCase {

    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp()
    {
        $this->queryBuilder = new MySQLQueryBuilder(new NullConnection());
    }

    public function test_buildColumnQuery()
    {
        $query = $this->queryBuilder->columns()
            ->table('users')
            ->build();

        $this->assertEquals("show columns from `users`;", $query->getSql());
    }

    public function test_buildCountQuery()
    {
        $query = $this->queryBuilder->count()
            ->table('users')
            ->build();

        $this->assertEquals("select count(*) as count from `users`;", $query->getSql());
    }

    public function test_buildCountQuery_withWhereConditions()
    {
        $query = $this->queryBuilder->count()
            ->table('users')
            ->whereEquals('unit', 'test')
            ->build();

        $this->assertEquals("select count(*) as count from `users` where unit = :unit;", $query->getSql());
        
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

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . ';', $query->getSql());
    }

    public function test_buildSelectQuery_specificColumns()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select(['id', 'email'])
            ->table('users', 'id', $alias)
            ->build();

        $this->assertEquals("select " . $alias . ".id as '" . $alias . ".id', " . $alias . ".email as '" . $alias . ".email' from `users` as " . $alias . ';', $query->getSql());
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
            ->group(function(QueryBuilder $queryBuilder) {
                $queryBuilder->whereEquals('group', 'test');
                $queryBuilder->whereEquals('biz', 'baz');
            })
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . " where " . $alias . ".unit = :" . $alias . "_unit and " . $alias . ".test != :" . $alias . "_test and " . $alias . ".foo is null and " . $alias . ".bar is not null and " . $alias . ".id in (1, 2, 3) and " . $alias . ".value between 1 and 10 and " . $alias . ".start between cast('1969-01-01' as datetime) and cast('1969-01-02' as datetime) and " . $alias . ".foo like :" . $alias . "_foo and (" . $alias . ".group = :" . $alias . "_group and " . $alias . ".biz = :" . $alias . "_biz);", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey($alias . '_unit', $bindParameters);
        $this->assertArrayHasKey($alias . '_test', $bindParameters);
        $this->assertArrayHasKey($alias . '_foo', $bindParameters);
        $this->assertArrayHasKey($alias . '_group', $bindParameters);
        $this->assertArrayHasKey($alias . '_biz', $bindParameters);
        $this->assertEquals('test', $bindParameters[$alias . '_unit']);
        $this->assertEquals('unit', $bindParameters[$alias . '_test']);
        $this->assertEquals('test%', $bindParameters[$alias . '_foo']);
        $this->assertEquals('test', $bindParameters[$alias . '_group']);
        $this->assertEquals('baz', $bindParameters[$alias . '_biz']);
    }

    public function test_buildSelectQuery_withLimit()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $alias)
            ->limit(3)
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . " limit 3;", $query->getSql());
    }

    public function test_buildSelectQuery_withOrder()
    {
        $alias = 'a0';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $alias)
            ->orderBy('id', 'desc')
            ->build();

        $this->assertEquals("select " . $alias . ".* as '" . $alias . ".*' from `users` as " . $alias . " order by " . $alias . ".id desc;", $query->getSql());
    }

    public function test_buildSelectQuery_withJoinLeft()
    {
        $usersAlias = 'a0';
        $phonesAlias = 'a1';
        $query = $this->queryBuilder->select()
            ->table('users', 'id', $usersAlias)
            ->joinLeft('phones', 'id', 'phone_id', [], $phonesAlias)
            ->build();

        $this->assertEquals("select " . $usersAlias . ".* as '" . $usersAlias . ".*' from `users` as " . $usersAlias . " left join `phones` as " . $phonesAlias . " on " . $usersAlias . ".phone_id = " . $phonesAlias . ".id;", $query->getSql());
    }

    public function test_buildSelectQuery_withGroup()
    {
        $query = $this->queryBuilder->select()
            ->table('users', 'id', null)
            ->group(function(QueryBuilder $queryBuilder) {
                $queryBuilder->whereEquals('email', 'unit@test.com');
                $queryBuilder->whereEquals('password', 'password');
            })
            ->build();

        $this->assertEquals("select * from `users` where (email = :email and password = :password);", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('email', $bindParameters);
        $this->assertArrayHasKey('password', $bindParameters);
        $this->assertEquals('unit@test.com', $bindParameters['email']);
        $this->assertEquals('password', $bindParameters['password']);
    }

    public function test_buildSelectQuery_withGroupOr()
    {
        $query = $this->queryBuilder->select()
            ->table('users', 'id', null)
            ->groupOr(function(QueryBuilder $queryBuilder) {
                $queryBuilder->whereEquals('email', 'unit@test.com');
                $queryBuilder->whereEquals('password', 'password');
            })
            ->build();

        $this->assertEquals("select * from `users` where (email = :email or password = :password);", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('email', $bindParameters);
        $this->assertArrayHasKey('password', $bindParameters);
        $this->assertEquals('unit@test.com', $bindParameters['email']);
        $this->assertEquals('password', $bindParameters['password']);
    }

    public function test_buildSelectQuery_withNestedGroups()
    {
        $query = $this->queryBuilder->select()
            ->table('users', 'id', null)
            ->group(function(QueryBuilder $queryBuilder) {
                $queryBuilder->whereEquals('email', 'unit@test.com');
                $queryBuilder->groupOr(function(QueryBuilder $queryBuilder) {
                    $queryBuilder->whereEquals('password', 'password');
                    $queryBuilder->whereNull('password');
                });
            })
            ->build();

        $this->assertEquals("select * from `users` where (email = :email and (password = :password or password is null));", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('email', $bindParameters);
        $this->assertArrayHasKey('password', $bindParameters);
        $this->assertEquals('unit@test.com', $bindParameters['email']);
        $this->assertEquals('password', $bindParameters['password']);
    }

    public function test_buildDeleteQuery()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->build();

        $this->assertEquals("delete from `users`;", $query->getSql());
    }

    public function test_buildDeleteQuery_withOrderAndLimit()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->orderBy('id')
            ->limit(2)
            ->build();

        $this->assertEquals("delete from `users` order by id asc limit 2;", $query->getSql());
    }

    public function test_buildDeleteQuery_withWhereConditions()
    {
        $query = $this->queryBuilder->delete()
            ->table('users')
            ->whereIn('id', [1,2,3])
            ->groupOr(function(QueryBuilder $queryBuilder){
                $queryBuilder->whereEquals('phone', '1234567890');
                $queryBuilder->whereEquals('zip', '12345');
            })
            ->build();

        $this->assertEquals("delete from `users` where id in (1, 2, 3) and (phone = :phone or zip = :zip);", $query->getSql());
    }

    public function test_buildUpdateQuery_withWhereConditions()
    {
        $columnData = [
            'email' => 'unit-test@test.com'
        ];

        $query = $this->queryBuilder->update($columnData)
            ->table('users')
            ->whereEquals('id', 1)
            ->group(function(QueryBuilder $queryBuilder){
                $queryBuilder->whereEquals('phone', '1234567890');
                $queryBuilder->whereEquals('zip', '12345');
            })
            ->build();

        $this->assertEquals("update `users` set email = :email where id = :id and (phone = :phone and zip = :zip);", $query->getSql());

        $bindParameters = $query->getBindParameters();
        $this->assertArrayHasKey('id', $bindParameters);
        $this->assertEquals(1, $bindParameters['id']);
    }

    public function test_buildCreateTableQuery()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email', 100);
        $blueprint->integer('age')->nullable()->unsigned();
        $blueprint->tinyInteger('tint')->nullable()->default(2);
        $blueprint->smallInteger('sint')->nullable();
        $blueprint->mediumInteger('mint')->nullable();
        $blueprint->bigInteger('bint')->nullable();
        $blueprint->numeric('price', 5, 2)->nullable();
        $blueprint->text('bio')->nullable();
        $blueprint->mediumText('mtext')->nullable();
        $blueprint->longText('ltext')->nullable();
        $blueprint->timestamp('created_at')->nullable();
        $blueprint->datetime('date_created')->nullable();

        $blueprint->unique('email');
        $blueprint->foreign('user_id', 'id', 'alt_users');
        $blueprint->index('email');

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table `users` (`id` int not null auto_increment, `email` varchar(100) not null, `age` int unsigned, `tint` tinyint default '2', `sint` smallint, `mint` mediumint, `bint` bigint, `price` decimal(5,2), `bio` text, `mtext` mediumtext, `ltext` longtext, `created_at` timestamp, `date_created` datetime, primary key (`id`), unique key unique_users_email (`email`), constraint user_id_alt_users_id foreign key (`user_id`) references `alt_users` (`id`), index (`email`)) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $query->getSql());
    }

    public function test_buildCreateTableQuery_withMultipleIndexes()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('id');
        $blueprint->string('email');
        $blueprint->string('age');

        $blueprint->index(['email', 'age']);

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table `users` (`id` int not null, `email` varchar(255) not null, `age` varchar(255) not null, index (`email`, `age`)) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $query->getSql());
    }

    public function test_buildCreateTableQuery_withMultipleUniqueKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('column_one');
        $blueprint->integer('column_two');

        $blueprint->unique(['column_one', 'column_two']);

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table `users` (`column_one` int not null, `column_two` int not null, unique key unique_users_column_one_column_two (`column_one`, `column_two`)) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $query->getSql());
    }

    public function test_buildCreateTableQuery_withMultiplePrimaryKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('column_one');
        $blueprint->integer('column_two');

        $blueprint->primary(['column_one', 'column_two']);

        $query = $this->queryBuilder->createTable($blueprint)->build();

        $this->assertEquals("create table `users` (`column_one` int not null, `column_two` int not null, primary key (`column_one`, `column_two`)) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $query->getSql());
    }

    public function test_buildCreateTableIfNotExistsQuery()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('id');
        $blueprint->string('email');
        $blueprint->string('age');

        $query = $this->queryBuilder->createTableIfNotExists($blueprint)->build();

        $this->assertEquals("create table if not exists `users` (`id` int not null, `email` varchar(255) not null, `age` varchar(255) not null) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $query->getSql());
    }

    public function test_buildDropTableQuery()
    {
        $query = $this->queryBuilder->dropTable('users')->build();

        $this->assertEquals("drop table `users`;", $query->getSql());
    }

    public function test_buildDropTableIfExistsQuery()
    {
        $query = $this->queryBuilder->dropTableIfExists('users')->build();

        $this->assertEquals("drop table if exists `users`;", $query->getSql());
    }

    public function test_buildSelectMaxQuery()
    {
        $query = $this->queryBuilder->table('users')->selectMax('score')->build();

        $this->assertEquals("select max(score) as max_value from `users`;", $query->getSql());
    }

    public function test_buildDropColumnsQuery()
    {
        $query = $this->queryBuilder->table('users')->dropColumns(['email'])->build();

        $this->assertEquals("alter table `users` drop column `email`;", $query->getSql());
    }

    public function test_buildDropColumnsQuery_withMultiple()
    {
        $query = $this->queryBuilder->table('users')->dropColumns(['email', 'name'])->build();

        $this->assertEquals("alter table `users` drop column `email`, drop column `name`;", $query->getSql());
    }

    public function test_buildAddColumnQuery()
    {
        $columnBlueprint = new ColumnBlueprint();
        $columnBlueprint->string('email', 25);

        $query = $this->queryBuilder->table('users')->addColumn($columnBlueprint)->build();

        $this->assertEquals("alter table `users` add column `email` varchar(25) not null;", $query->getSql());
    }

    public function test_buildCreateIndexQuery()
    {
        $query = $this->queryBuilder->table('users')->createIndex(['id'], 'index_name')->build();

        $this->assertEquals("create index index_name on users (id);", $query->getSql());
    }

    public function test_buildCreateIndexQuery_withMultipleColumns()
    {
        $query = $this->queryBuilder->table('users')->createIndex(['id', 'email'], 'index_name')->build();

        $this->assertEquals("create index index_name on users (id, email);", $query->getSql());
    }

    public function test_buildDropIndexQuery()
    {
        $query = $this->queryBuilder->table('users')->dropIndex('index_name')->build();

        $this->assertEquals("alter table `users` drop index index_name;", $query->getSql());
    }

    public function test_buildAddForeignKeyQuery()
    {
        $query = $this->queryBuilder->table('users')->addForeignKey('from_column', 'to_column', 'on_table', null, 'foreign_key')->build();

        $this->assertEquals("alter table `users` add constraint foreign_key foreign key (`from_column`) references `on_table` (`to_column`);", $query->getSql());
    }

    public function test_buildDropForeignKeyQuery()
    {
        $query = $this->queryBuilder->table('users')->dropForeignKey('foreign_key')->build();

        $this->assertEquals("alter table `users` drop foreign key foreign_key;", $query->getSql());
    }
    
}