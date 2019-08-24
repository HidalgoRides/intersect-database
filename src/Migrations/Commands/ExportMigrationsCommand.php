<?php

namespace Intersect\Database\Migrations\Commands;

use Intersect\Core\Storage\FileStorage;
use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Migrations\Runner;

class ExportMigrationsCommand extends AbstractCommand {

    /** @var FileStorage */
    private $fileStorage;

    /** @var Runner */
    private $runner;

    public function __construct(Connection $connection, array $migrationDirectories)
    {
        parent::__construct();
        $this->fileStorage = new FileStorage();
        $this->runner = new Runner($connection, $this->fileStorage, $this->logger, $migrationDirectories);
    }

    public function getDescription()
    {
        return 'Exports all migration queries to SQL file';
    }

    public function getParameters()
    {
        return [
            'path' => 'Path to save the exported SQL file',
            'action' => '"--seed" include seed data (optional)'
        ];
    }

    public function execute($data = [])
    {
        if (!isset($data[0]) || !$this->fileStorage->directoryExists($data[0]))
        {
            $this->logger->warn('Please enter a valid path');
            exit();
        }

        $path = $data[0];
        $action = (isset($data[1]) ? $data[1] : null);

        $includeSeedData = false;

        if ($action == '--seed')
        {
            $includeSeedData = true;
        } 

        $this->runner->export($path, $includeSeedData);
    }

}