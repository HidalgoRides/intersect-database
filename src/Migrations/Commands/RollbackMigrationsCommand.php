<?php

namespace Intersect\Database\Migrations\Commands;

use Intersect\Core\Storage\FileStorage;
use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Migrations\Runner;

class RollbackMigrationsCommand extends AbstractCommand {

    /** @var Runner */
    private $runner;

    public function __construct(Connection $connection, array $migrationDirectories)
    {
        parent::__construct();
        $this->runner = new Runner($connection, new FileStorage(), $this->logger, $migrationDirectories);
    }

    public function getDescription()
    {
        return 'Rollback all migration scripts, or only migration scripts from last batch ran';
    }
    
    public function getParameters()
    {
        return [
            'action' => '"--last" (optional)'
        ];
    }

    public function execute($data = [])
    {
        $action = (isset($data[0]) ? $data[0] : null);

        if ($action == '--last')
        {
            $this->runner->rollbackLastBatch();
        }
        else
        {
            $this->runner->rollback();
        }
    }

}