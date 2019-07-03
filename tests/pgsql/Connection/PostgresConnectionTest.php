<?php

namespace Tests\Connection;

use Tests\TestUtility;
use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\ColumnDefinition;

class PostgresConnectionTest extends TestCase {

    /** @var Connection */
    private $connection;

    protected function setUp()
    {
        parent::setUp();

        $this->connection = TestUtility::getPostgresConnection('integration_tests');
    }

    protected function tearDown()
    {
        $this->connection->closeConnection();
    }

    public function test_query_cacheUpdatedIfDirty()
    {
        $result = $this->connection->query("SELECT * FROM public.users ORDER BY id DESC LIMIT 1");

        $record = $result->getRecords()[0];

        $id = $record['id'];
        $updatedData = 'Updated email ' . uniqid();

        $this->connection->query("UPDATE public.users SET email=:email WHERE id=:id", [
            'email' => $updatedData,
            'id' => $id
        ]);

        $result = $this->connection->query("SELECT * FROM public.users ORDER BY id DESC LIMIT 1");

        $record = $result->getRecords()[0];

        $this->assertEquals($updatedData, $record['email']);
    }

    public function test_query_resultsReturnedIfSqlHasSpacesAtBeginning()
    {
        $result = $this->connection->query("SELECT * FROM public.users");

        $this->assertTrue($result->getCount() > 0);

        $result = $this->connection->query("
            SELECT * FROM public.users
        ");
        $this->assertTrue($result->getCount() > 0);
    }

    public function test_queryBuilder_dropColumns()
    {
        $tableName = 'test_querybuilder_dropcolumns';

        $blueprint = new Blueprint($tableName);
        $blueprint->integer('id');
        $blueprint->string('name');

        $this->connection->getQueryBuilder()->createTable($blueprint)->get();

        $result = $this->connection->getQueryBuilder()->table($tableName)->columns()->get(true);
        $columns = array_column($result->getRecords(), 'Field');

        $this->assertCount(2, $columns);

        $this->connection->getQueryBuilder()->table($tableName)->dropColumns(['name'])->get();

        $result = $this->connection->getQueryBuilder()->table($tableName)->columns()->get(true);
        $columns = array_column($result->getRecords(), 'Field');

        $this->assertCount(1, $columns);
    }

    public function test_queryBuilder_addColumn()
    {
        $tableName = 'test_querybuilder_addcolumn';

        $blueprint = new Blueprint($tableName);
        $blueprint->integer('id');
        $blueprint->string('name');

        $this->connection->getQueryBuilder()->createTable($blueprint)->get();

        $result = $this->connection->getQueryBuilder()->table($tableName)->columns()->get(true);
        $columns = array_column($result->getRecords(), 'Field');

        $this->assertCount(2, $columns);

        $columnDefinition = new ColumnDefinition('email', ColumnType::STRING);
        $columnDefinition->length(25);

        $this->connection->getQueryBuilder()->table($tableName)->addColumn($columnDefinition)->get();

        $result = $this->connection->getQueryBuilder()->table($tableName)->columns()->get(true);
        $columns = array_column($result->getRecords(), 'Field');

        $this->assertCount(3, $columns);
    }

}