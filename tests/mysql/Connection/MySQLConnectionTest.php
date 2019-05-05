<?php

namespace Tests\Connection;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;

class MySQLConnectionTest extends TestCase {

    /** @var Connection */
    private $connection;

    public function setUp()
    {
        parent::setUp();

        $connectionSettings = new ConnectionSettings('db', 'root', 'password', 3306, 'app');
        $this->connection = ConnectionFactory::get('mysql', $connectionSettings);
    }

    public function test_query_cacheUpdatedIfDirty()
    {
        $result = $this->connection->query("SELECT * FROM users");

        $record = $result->getRecords()[0];

        $id = $record['id'];
        $updatedData = 'Updated email ' . uniqid();

        $this->connection->query("UPDATE users SET email=:email WHERE id=:id LIMIT 1", [
            'email' => $updatedData,
            'id' => $id
        ]);

        $result = $this->connection->query("SELECT * FROM users");

        $record = $result->getRecords()[0];

        $this->assertEquals($updatedData, $record['email']);
    }

    public function test_query_resultsReturnedIfSqlHasSpacesAtBeginning()
    {
        // <b>test = if mwhazle <i>loves</i> mwahzle then NOOCH</b> 9/24/18
        $result = $this->connection->query("SELECT * FROM users");
        $this->assertTrue($result->getCount() > 0);

        $result = $this->connection->query("
            SELECT * FROM users
        ");
        $this->assertTrue($result->getCount() > 0);
    }

}