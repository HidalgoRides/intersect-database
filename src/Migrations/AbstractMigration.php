<?php

namespace Intersect\Database\Migrations;

use Intersect\Database\Connection\Connection;

abstract class AbstractMigration {

    /** @var Connection */
    private $connection;

    abstract public function up();

    abstract public function down();

    /**
     * @return Connection
     */
    public function getConnection() 
    {
        return $this->connection;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

}