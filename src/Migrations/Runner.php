<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Logger\Logger;
use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Exception\DatabaseException;

class Runner {

    private $currentAction = 'up';

    /** @var Connection */
    private $connection;

    /** @var FileStorage */
    private $fileStorage;

    /** @var Logger */
    private $logger;

    private $currentBatchId;

    private $migrationDirectory;

    public function __construct(Connection $connection, FileStorage $fileStorage, Logger $logger, $migrationsPath)
    {
        $this->connection = $connection;
        $this->fileStorage = $fileStorage;
        $this->logger = $logger;
        $this->migrationDirectory = $migrationsPath;
    }

    public function setMigrationDirectory($migrationDirectory)
    {
        $this->migrationDirectory = $migrationDirectory;
    }

    public function getCurrentBatchId()
    {
        return $this->currentBatchId;
    }

    public function migrate()
    {
        $this->checkForMigrationTable();

        $this->logger->info('Starting migration');

        $migrationPaths = $this->fileStorage->glob(rtrim($this->migrationDirectory, '/') . '/*_*.php');

        $filteredMigrationPaths = $this->getFilteredMigrationPaths($migrationPaths);

        if (count($filteredMigrationPaths) == 0)
        {
            $this->logger->warn('Nothing to migrate!');
            return;
        }

        $lastBatchId = $this->getLastBatchId();
        $this->currentBatchId = ($lastBatchId + 1);

        foreach ($filteredMigrationPaths as $path)
        {
            try {
                $this->migratePath($path);
            } catch (\Exception $e) {
                $this->logger->error('An error occurred during migration of file: ' . $path);
                $this->logger->error(' * ' . $e->getMessage());
                return;
            }
        }

        $this->logger->info('Finished migration');
    }

    public function rollbackLastBatch()
    {
        $this->checkForMigrationTable();
        $lastBatchId = $this->getLastBatchId();
        $this->rollback($lastBatchId);
    }

    public function rollback($batchId = null)
    {
        $this->checkForMigrationTable();

        $this->logger->info('Starting rollback' . (!is_null($batchId) ? ' for batch id: ' . $batchId : ''));

        $migrationsToRollback = $this->getMigrationsToRollback($batchId);

        if (count($migrationsToRollback) == 0)
        {
            $this->logger->warn('Nothing to rollback!');
            return;
        }

        foreach ($migrationsToRollback as $migration)
        {
            $this->rollbackMigration($migration);
        }

        $this->logger->info('Finished rollback');
    }

    private function checkForMigrationTable()
    {
        try {
            Migration::findOne();
        } catch (DatabaseException $e) {
            $this->logger->info('Creating migrations table');
            $installCommand = new InstallMigrationsCommand($this->connection);
            $installCommand->execute();
            $this->logger->info('Finished creating migrations table');
        }
    }

    /**
     * @param $migrationFiles
     * @return array
     * @throws DatabaseException
     */
    private function getFilteredMigrationPaths($migrationFiles)
    {
        $migrationParameters = new QueryParameters();
        $migrationParameters->equals('status', 1);

        $existingMigrations = Migration::find($migrationParameters);
        $existingMigrationNames = array_column($existingMigrations, 'name');

        $filteredMigrationPaths = [];
        foreach ($migrationFiles as $migrationFile)
        {
            $migrationFileParts = explode('/', $migrationFile);
            $migrationFileName = end($migrationFileParts);

            if (in_array($migrationFileName, $existingMigrationNames))
            {
                continue;
            }

            $filteredMigrationPaths[] = $migrationFile;
        }

        return $filteredMigrationPaths;
    }

    /** @return Migration[] */
    private function getMigrationsToRollback($batchId = null)
    {
        $migrationParameters = new QueryParameters();
        $migrationParameters->equals('status', 1);
        $migrationParameters->setOrder('id desc');

        if (!is_null($batchId))
        {
            $migrationParameters->equals('batch_id', $batchId);
        }

        return Migration::find($migrationParameters);
    }


    /**
     * @param $path
     * @throws DatabaseException
     * @throws \Intersect\Database\Exception\ValidationException
     */
    private function migratePath($path)
    {
        $this->logger->info('Migrating file: ' . $path);

        $this->fileStorage->requireOnce($path);

        $className = MigrationHelper::resolveClassNameFromPath($path);
        $class = new $className($this->connection);

        if (!$class instanceof AbstractMigration)
        {
            $this->logger->error($path . ' is not an instance of Migration. Skipping file');
            return;
        }

        $class->up();

        $pathParts = explode('/', $path);

        $migration = new Migration();
        $migration->name = end($pathParts);
        $migration->batch_id = $this->currentBatchId;
        $migration->save();
    }

    private function rollbackMigration(Migration $migration)
    {
        $migrationFile = $migration->name;
        $migrationFileFullPath = $this->migrationDirectory . '/' . $migrationFile;

        $this->logger->info('Rolling back file: ' . $migrationFile);

        $this->fileStorage->requireOnce($migrationFileFullPath);

        $className = MigrationHelper::resolveClassNameFromPath($migrationFile);
        $class = new $className($this->connection);

        if (!$class instanceof AbstractMigration)
        {
            $this->logger->error($migrationFileFullPath . ' is not an instance of Migration. Skipping file');
            return;
        }

        $class->down();

        $migration->status = 2;
        $migration->save();
    }

    private function getLastBatchId()
    {
        $result = $this->connection->getQueryBuilder()->selectMax('batch_id')->table('ic_migrations')->get();
        return (int) ($result->getFirstRecord()['max_value']);
    }

}