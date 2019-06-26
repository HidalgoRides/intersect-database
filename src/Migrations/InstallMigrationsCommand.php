<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\Schema;
use Intersect\Database\Schema\Blueprint;

class InstallMigrationsCommand extends AbstractCommand {

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    public function getDescription()
    {
        return 'Creates the database table required for performing migrations';
    }

    public function execute($data = [])
    {
        $schema = new Schema($this->connection);

        $schema->createTable('ic_migrations', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('name');
            $blueprint->tinyInteger('status')->default(1);
            $blueprint->integer('batch_id');
            $blueprint->datetime('date_created');
            $blueprint->datetime('date_updated')->nullable();
        });
    }

}