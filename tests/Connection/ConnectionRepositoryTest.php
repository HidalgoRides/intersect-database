<?php

namespace Tests\Connection;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Connection\ConnectionRepository;
use Intersect\Database\Connection\NullConnection;

class ConnectionRepositoryTest extends TestCase {

    public function test_get()
    {
        $key = 'test-get';
        ConnectionRepository::register($key, new NullConnection());
        $this->assertNotNull(ConnectionRepository::get($key));
    }

    public function test_register()
    {
        $key = 'test-register';
        $this->assertNull(ConnectionRepository::get($key));
        ConnectionRepository::register($key, new NullConnection());
        $this->assertNotNull(ConnectionRepository::get($key));
    }

}