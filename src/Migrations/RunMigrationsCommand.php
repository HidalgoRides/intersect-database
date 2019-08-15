<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Storage\FileStorage;
use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;

class RunMigrationsCommand extends AbstractCommand {

    /** @var Runner */
    private $runner;

    public function __construct(Connection $connection, array $migrationDirectories)
    {
        parent::__construct();
        $this->runner = new Runner($connection, new FileStorage(), $this->logger, $migrationDirectories);
    }

    public function getDescription()
    {
        return 'Runs all migration scripts, if needed, in your migrations directory';
    }
    
    public function getParameters()
    {
        return [
            'action' => '"--rollback" or "--rollbackLast" (optional)'
        ];
    }

    public function execute($data = [])
    {
        $action = (isset($data[0]) ? $data[0] : null);

        if ($action == '--rollback')
        {
            $this->runner->rollback();
        } 
        if ($action == '--rollbackLast')
        {
            $this->runner->rollbackLastBatch();
        }
        else
        {
            $this->runner->migrate();
        }
    }

}