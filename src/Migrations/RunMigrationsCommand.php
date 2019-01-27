<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Storage\FileStorage;
use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;

class RunMigrationsCommand extends AbstractCommand {

    /** @var Runner */
    private $runner;

    public function __construct(Connection $connection, $migrationsPath)
    {
        parent::__construct();
        $this->runner = new Runner($connection, new FileStorage(), $this->logger, $migrationsPath);
    }

    public function getDescription()
    {
        return 'Runs all migration scripts, if needed, in your migrations directory';
    }

    public function execute($data = [])
    {
        $this->runner->run();
    }

}