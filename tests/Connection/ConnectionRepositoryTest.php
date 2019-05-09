<?php

namespace Tests\Connection;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\ConnectionRepository;
use Intersect\Database\Connection\NullConnection;

class ConnectionRepositoryTest extends TestCase {

    protected function setUp()
    {
        parent::setUp();
        ConnectionRepository::clearConnections();
    }

    public function test_get()
    {
        ConnectionRepository::register('test', new NullConnection());

        $this->assertNull(ConnectionRepository::get('invalid'));
        $this->assertNotNull(ConnectionRepository::get('test'));
    }

    public function test_register()
    {
        $this->assertCount(0, ConnectionRepository::getConnections());

        ConnectionRepository::register('test', new NullConnection());

        $this->assertCount(1, ConnectionRepository::getConnections());
    }

}