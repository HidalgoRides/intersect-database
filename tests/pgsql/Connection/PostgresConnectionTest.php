<?php

namespace Tests\Connection;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;

class PostgresConnectionTest extends TestCase {

    /** @var Connection */
    private $connection;

    public function setUp()
    {
        parent::setUp();

        $connectionSettings = new ConnectionSettings('db-postgres', 'root', 'password', 5432, 'app');
        $this->connection = ConnectionFactory::get('pgsql', $connectionSettings);
    }

    public function test_query_cacheUpdatedIfDirty()
    {
        $result = $this->connection->query("SELECT * FROM users ORDER BY id DESC LIMIT 1");

        $record = $result->getRecords()[0];

        $id = $record['id'];
        $updatedData = 'Updated email ' . uniqid();

        $this->connection->query("UPDATE users SET email=:email WHERE id=:id", [
            'email' => $updatedData,
            'id' => $id
        ]);

        $result = $this->connection->query("SELECT * FROM users ORDER BY id DESC LIMIT 1");

        $record = $result->getRecords()[0];

        $this->assertEquals($updatedData, $record['email']);
    }

    public function test_query_resultsReturnedIfSqlHasSpacesAtBeginning()
    {
        $result = $this->connection->query("SELECT * FROM users");

        $this->assertTrue($result->getCount() > 0);

        $result = $this->connection->query("
            SELECT * FROM users
        ");
        $this->assertTrue($result->getCount() > 0);
    }

}