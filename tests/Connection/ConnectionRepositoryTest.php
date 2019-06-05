<?php

namespace Tests\Connection;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\ConnectionRepository;
use Intersect\Database\Connection\NullConnection;

class ConnectionRepositoryTest extends TestCase {

    public function test_get()
    {
        $key = 'test-get';
        ConnectionRepository::register(new NullConnection(), $key);
        $this->assertNotNull(ConnectionRepository::get($key));
    }

    public function test_register()
    {
        $key = 'test-register';
        $this->assertNull(ConnectionRepository::get($key));
        ConnectionRepository::register(new NullConnection(), $key);
        $this->assertNotNull(ConnectionRepository::get($key));
    }

    public function test_registerAlias()
    {
        $key = 'test-register';
        $alias = 'test-alias';
        $this->assertNull(ConnectionRepository::get($alias));

        $connection = new NullConnection();

        ConnectionRepository::register($connection, $key);
        ConnectionRepository::registerAlias($alias, $key);

        $this->assertNotNull(ConnectionRepository::get($alias));
        $this->assertEquals($connection, ConnectionRepository::get($alias));
    }

}