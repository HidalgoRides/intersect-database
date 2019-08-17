<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Logger\Logger;
use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Migrations\Migration;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Migrations\MigrationHelper;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Migrations\ExportConnection;
use Intersect\Database\Connection\ConnectionRepository;
use Intersect\Database\Migrations\InstallMigrationsCommand;

class Runner {

    /** @var Connection */
    private $connection;

    /** @var FileStorage */
    private $fileStorage;

    /** @var Logger */
    private $logger;

    private $currentBatchId;

    private $migrationDirectories = [];

    private $seedMigrationsEnabled;

    public function __construct(Connection $connection, FileStorage $fileStorage, Logger $logger, array $migrationDirectories = [])
    {
        $this->connection = $connection;
        $this->fileStorage = $fileStorage;
        $this->logger = $logger;
        $this->migrationDirectories = $migrationDirectories;
    }

    public function setMigrationDirectories(array $migrationDirectories)
    {
        $this->migrationDirectories = $migrationDirectories;
    }

    public function getCurrentBatchId()
    {
        return $this->currentBatchId;
    }

    public function export($exportPath, $includeSeedData = false)
    {
        $this->logger->info('Starting export');

        $contents = '-- Intersect Migration Exporter' . PHP_EOL;
        $contents .= '-- Generated on ' . date('Y-m-d H:i:s');

        $oldConnection = $this->connection;
        $queryContents = '';

        foreach ($this->migrationDirectories as $migrationDirectory)
        {
            $migrationPaths = $this->fileStorage->glob(rtrim($migrationDirectory, '/') . '/*_*.php');
    
            foreach ($migrationPaths as $migrationPath)
            {
                $exportConnection = new ExportConnection($oldConnection);
                ConnectionRepository::register($exportConnection);

                $this->fileStorage->requireOnce($migrationPath);
    
                $className = MigrationHelper::resolveClassNameFromPath($migrationPath);
                $class = new $className($exportConnection);
    
                if (!$class instanceof AbstractMigration && !$class instanceof AbstractSeed)
                {
                    $this->logger->error($migrationPath . ' is not an instance of AbstractMigration or AbstractSeed. Skipping file');
                    return;
                }
    
                if ($class instanceof AbstractMigration)
                {
                    $this->logger->info('Exporting migration: ' . $migrationPath);
                    $queryContents .= PHP_EOL . PHP_EOL . '-- File: ' . $migrationPath;
                    $class->up();
                }
                else if ($class instanceof AbstractSeed && $includeSeedData)
                {
                    $this->logger->info('Exporting seed data: ' . $migrationPath);
                    $queryContents .= PHP_EOL . PHP_EOL . '-- File: ' . $migrationPath;
                    $class->populate();
                }

                foreach ($exportConnection->getQueries() as $query)
                {
                    $queryContents .= PHP_EOL . $query;
                }
            }
        }

        $exportedFileName = 'export_' . strtolower($oldConnection->getDriver()) . '_' . date('Y_m_d_His') . '.sql';
        $exportedFilePath = $exportPath . '/' . $exportedFileName;

        $contents .= $queryContents . PHP_EOL . PHP_EOL . '-- End of export';

        ConnectionRepository::register($oldConnection);
        $this->connection = $oldConnection;

        $this->fileStorage->writeFile($exportedFilePath, $contents);

        $this->logger->info('Export finished. File saved to: ' . $exportedFilePath);

        return $exportedFilePath;
    }

    public function migrate($seedMigrationsEnabled = false)
    {
        $this->checkForMigrationTable();
        $this->seedMigrationsEnabled = $seedMigrationsEnabled;

        $this->logger->info('Starting migration');

        $allMigrationsToRun = [];

        foreach ($this->migrationDirectories as $migrationDirectory)
        {
            $migrationPaths = $this->fileStorage->glob(rtrim($migrationDirectory, '/') . '/*_*.php');

            $migrationsToRun = $this->getMigrationToRun($migrationPaths);
    
            if (count($migrationsToRun) == 0)
            {
                continue;
            }

            $allMigrationsToRun = array_merge($allMigrationsToRun, $migrationsToRun);
        }

        if (count($allMigrationsToRun) == 0)
        {
            $this->logger->info('No migrations to run');
        }
        else
        {
            $lastBatchId = $this->getLastBatchId();
            $this->currentBatchId = ($lastBatchId + 1);

            // sort migrations by name so that all sources are run according to date created and not per directory load order
            usort($allMigrationsToRun, function($m1, $m2) {
                return strcmp($m1->name, $m2->name);
            });
    
            /** @var Migration $migration */
            foreach ($allMigrationsToRun as $migration)
            {
                try {
                    $this->runMigration($migration);
                } catch (\Exception $e) {
                    $this->logger->error('An error occurred during migration of file: ' . $migration->name);
                    $this->logger->error(' * ' . $e->getMessage());
                    return;
                }
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
     * @return Migration[]
     * @throws DatabaseException
     */
    private function getMigrationToRun(array $migrationFiles)
    {
        if (count($migrationFiles) == 0)
        {
            return [];
        }

        $migrationParameters = new QueryParameters();
        $existingMigrations = Migration::find($migrationParameters);

        $existingMigrationsWithKeys = [];
        foreach ($existingMigrations as $existingMigration)
        {
            $existingMigrationsWithKeys[md5($existingMigration->name)] = $existingMigration;
        }

        $migrationsToRun = [];

        foreach ($migrationFiles as $migrationFile)
        {
            $migrationFileParts = explode('/', $migrationFile);
            $migrationFileName = end($migrationFileParts);

            $migrationToRun = null;

            $migrationFileNameHash = md5($migrationFileName);
            if (array_key_exists($migrationFileNameHash, $existingMigrationsWithKeys))
            {
                $migration = $existingMigrationsWithKeys[$migrationFileNameHash];

                if ($migration->status != 1)
                {
                    $migrationToRun = $migration;
                }
            }
            else
            {
                $migrationToRun = new Migration();
                $migrationToRun->name = $migrationFileName;
                $migrationToRun->path = $migrationFile;
            }

            if (!is_null($migrationToRun))
            {
                $migrationsToRun[] = $migrationToRun;
            }
        }

        return $migrationsToRun;
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
     * @param Migration $migration
     * @throws DatabaseException
     * @throws \Intersect\Database\Exception\ValidationException
     */
    private function runMigration(Migration $migration)
    {
        $fileName = $migration->name;

        $this->logger->info('Migrating file: ' . $fileName);

        $this->fileStorage->requireOnce($migration->path);

        $className = MigrationHelper::resolveClassNameFromPath($fileName);
        $class = new $className($this->connection);

        if (!$class instanceof AbstractMigration && !$class instanceof AbstractSeed)
        {
            $this->logger->error($fileName . ' is not an instance of AbstractMigration or AbstractSeed. Skipping file');
            return;
        }

        if ($class instanceof AbstractMigration)
        {
            $class->up();
        }
        else if ($class instanceof AbstractSeed)
        {
            $class->populate();
        }

        $migration->batch_id = $this->currentBatchId;
        $migration->status = 1;
        $migration->save();
    }

    private function rollbackMigration(Migration $migration)
    {
        $migrationFile = $migration->name;
        $migrationFileFullPath = $migration->path;

        $this->logger->info('Rolling back file: ' . $migrationFile);

        if (!$this->fileStorage->fileExists($migrationFileFullPath))
        {
            $this->logger->error($migrationFileFullPath . ' not found. Aborting rollback');
            die();
        }

        $this->fileStorage->requireOnce($migrationFileFullPath);

        $className = MigrationHelper::resolveClassNameFromPath($migrationFile);
        $class = new $className($this->connection);

        if (!$class instanceof AbstractMigration && !$class instanceof AbstractSeed)
        {
            $this->logger->error($migrationFileFullPath . ' is not an instance of AbstractMigration or AbstractSeed. Skipping file');
            return;
        }

        if ($class instanceof AbstractMigration)
        {
            $class->down();
        }

        $migration->status = 2;
        $migration->save();
    }

    private function getLastBatchId()
    {
        $result = $this->connection->getQueryBuilder()->selectMax('batch_id')->table('ic_migrations')->get();
        return (int) ($result->getFirstRecord()['max_value']);
    }

}