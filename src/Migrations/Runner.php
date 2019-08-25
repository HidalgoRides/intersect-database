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

class Runner {

    /** @var Connection */
    private $connection;

    /** @var FileStorage */
    private $fileStorage;

    /** @var Logger */
    private $logger;

    private $currentBatchId;

    private $migrationDirectories = [];

    private $seedMigrationsEnabled = false;

    public function __construct(Connection $connection, FileStorage $fileStorage, Logger $logger, array $migrationDirectories = [])
    {
        $this->connection = $connection;
        $this->fileStorage = $fileStorage;
        $this->logger = $logger;
        $this->setMigrationDirectories($migrationDirectories);
    }

    public function setMigrationDirectories(array $migrationDirectories)
    {
        $migrationDirectories = array_merge([dirname(__FILE__) . '/Migrations'], $migrationDirectories);
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

        $allMigrationPathsToExport = [];

        foreach ($this->migrationDirectories as $migrationDirectory)
        {
            $migrationPaths = $this->fileStorage->glob(rtrim($migrationDirectory, '/') . '/*_*.php');

            if (count($migrationPaths) == 0)
            {
                continue;
            }

            $allMigrationPathsToExport = array_merge($allMigrationPathsToExport, $migrationPaths);
        }

        $exportedFilePath = null;

        if (count($allMigrationPathsToExport) == 0)
        {
            $this->logger->info('No migrations to export');
        }
        else
        {   
            // sort migrations by name so that all sources are run according to date created and not per directory load order
            $allMigrationPathsToExport = $this->sortMigrationPaths($allMigrationPathsToExport);

            foreach ($allMigrationPathsToExport as $migrationPath)
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

            // reset connection back to original connection
            ConnectionRepository::register($oldConnection);
            $this->connection = $oldConnection;
            
            $exportedFileName = 'export_' . strtolower($oldConnection->getDriver()) . '_' . date('Y_m_d_His') . '.sql';
            $exportedFilePath = $exportPath . '/' . $exportedFileName;
    
            $contents .= $queryContents . PHP_EOL . PHP_EOL . '-- End of export';
    
            $this->fileStorage->writeFile($exportedFilePath, $contents);
    
            $this->logger->info('Export finished. File saved to: ' . $exportedFilePath);
        }

        return $exportedFilePath;
    }    

    public function migrate($seedMigrationsEnabled = false)
    {
        $this->seedMigrationsEnabled = $seedMigrationsEnabled;

        $this->logger->info('Starting migration');

        $allMigrationsToRun = [];

        foreach ($this->migrationDirectories as $migrationDirectory)
        {
            $migrationPaths = $this->fileStorage->glob(rtrim($migrationDirectory, '/') . '/*_*.php');

            $migrationsToRun = $this->getMigrationsToRun($migrationPaths);
    
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
                return strcmp($this->getTimeFromMigrationPath($m1->path), $this->getTimeFromMigrationPath($m2->name));
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
            $this->logger->error('Migrations table "ic_migrations" does not exist...');
            throw new \Exception('Migrations table "ic_migrations" does not exist...');
        }
    }

    private function getLastBatchId()
    {
        $lastBatchId = 0;

        try {
            $result = $this->connection->getQueryBuilder()->selectMax('batch_id')->table('ic_migrations')->whereEquals('status', 1)->get();
            $lastBatchId = (int) ($result->getFirstRecord()['max_value']);
        } catch (DatabaseException $e) {}
        
        return $lastBatchId;
    }

    /**
     * @param $migrationFiles
     * @return Migration[]
     * @throws DatabaseException
     */
    private function getMigrationsToRun(array $migrationFiles)
    {
        if (count($migrationFiles) == 0)
        {
            return [];
        }

        $migrationParameters = new QueryParameters();
        $existingMigrations = [];

        try {
            $existingMigrations = Migration::find($migrationParameters);
        } catch (DatabaseException $e) {}

        $existingMigrationsWithKeys = [];
        foreach ($existingMigrations as $existingMigration)
        {
            $existingMigrationsWithKeys[md5($existingMigration->name)] = $existingMigration;
        }

        $migrationsToRun = [];

        foreach ($migrationFiles as $migrationFile)
        {
            $this->fileStorage->requireOnce($migrationFile);
    
            $className = MigrationHelper::resolveClassNameFromPath($migrationFile);
            $class = new $className($this->connection);

            if ($class instanceof AbstractSeed && !$this->seedMigrationsEnabled)
            {
                continue;
            }

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

    private function getTimeFromMigrationPath($migrationPath)
    {
        $migrationPathParts = explode('/', str_replace('.php', '', $migrationPath));
        $migrationPathParts = explode('_', end($migrationPathParts));
        return end($migrationPathParts);
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

        if ($class->skipMigration)
        {
            $this->logger->error($fileName . ' has skipMigration set to true. Skipping file');
        }
        else
        {
            if ($class instanceof AbstractMigration)
            {
                $class->up();
            }
            else if ($class instanceof AbstractSeed)
            {
                $class->populate();
            }
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
            $this->logger->error($migrationFileFullPath . ' not found. Skipping migration rollback for this file');
            return;
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

    private function sortMigrationPaths(array $migrationPaths)
    {
        usort($migrationPaths, function($a, $b) {
            return strcmp($this->getTimeFromMigrationPath($a), $this->getTimeFromMigrationPath($b));
        });

        return $migrationPaths;
    }

}