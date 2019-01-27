<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;

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
        $this->connection->query("CREATE TABLE ic_migrations (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            status TINYINT(2) NOT NULL DEFAULT 1,
            date_created DATETIME NOT NULL,
            date_updated DATETIME
        )");
    }

}