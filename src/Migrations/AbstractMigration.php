<?php

namespace Intersect\Database\Migrations;

use Intersect\Database\Schema\Schema;
use Intersect\Database\Connection\Connection;

abstract class AbstractMigration {

    /** @var Schema */
    protected $schema;

    public function __construct(Connection $connection)
    {
        $this->schema = new Schema($connection);
    }

    abstract public function up();
    abstract public function down();

}