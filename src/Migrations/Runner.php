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
use Intersect\Database\Migrations\MigrationFetcher;
use Intersect\Database\Connection\ConnectionRepository;

class Runner {

    /** @var Connection */
    private $connection;

    /** @var FileStorage */
    private $fileStorage;

    /** @var Logger */
    private $logger;

    /** @var MigrationFetcher */
    private $migrationFetcher;

    private $currentBatchId;

    private $migrationDirectories = [];

    private $seedMigrationsEnabled = false;

    public function __construct(Connection $connection, FileStorage $fileStorage, Logger $logger, array $migrationDirectories = [])
    {
        $this->connection = $connection;
        $this->fileStorage = $fileStorage;
        $this->logger = $logger;
        $this->setMigrationDirectories($migrationDirectories);

        $this->migrationFetcher = new MigrationFetcher($connection);
    }

    public function setMigrationDirectories(array $migrationDirectories)
    {
        $migrationDirectories = array_merge($migrationDirectories, [dirname(__FILE__) . '/Migrations']);
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

        $migrationsToExport = $this->migrationFetcher->fetch($this->migrationDirectories, $includeSeedData, true);

        $exportedFilePath = null;

        if (count($migrationsToExport) == 0)
        {
            $this->logger->info('No migrations to export');
        }
        else
        {   
            foreach ($migrationsToExport as $migration)
            {
                $exportConnection = new ExportConnection($oldConnection);
                ConnectionRepository::register($exportConnection);

                $migrationPath = $migration->path;

                $this->fileStorage->requireOnce($migrationPath);
                $className = MigrationHelper::resolveClassNameFromPath($migrationPath);
                $class = new $className($exportConnection);
    
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

        $allMigrationsToRun = $this->migrationFetcher->fetch($this->migrationDirectories, $seedMigrationsEnabled, false);

        if (count($allMigrationsToRun) == 0)
        {
            $this->logger->info('No migrations to run');
        }
        else
        {
            $lastBatchId = $this->getLastBatchId();
            $this->currentBatchId = ($lastBatchId + 1);

            /** @var Migration $migration */
            foreach ($allMigrationsToRun as $migration)
            {
                try {
                    $this->runMigration($migration);
                } catch (\Exception $e) {
                    $this->logger->error('An error occurred during migration of file: ' . $migration->name);
                    $this->logger->error(' * ' . $e->getMessage());
                    $this->logger->info('Rolling back migrations...');
                    $this->rollback($this->currentBatchId);
                    return;
                }
            }
        }

        $this->logger->info('Finished migration');
    }

    public function rollbackLastBatch()
    {
        if (!$this->migrationTableExists())
        {
            $this->logger->info('Nothing to rollback!');
            return;
        }

        $lastBatchId = $this->getLastBatchId();
        $this->rollback($lastBatchId);
    }

    public function rollback($batchId = null)
    {
        if (!$this->migrationTableExists())
        {
            $this->logger->info('Nothing to rollback!');
            return;
        }

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

    private function migrationTableExists()
    {
        try {
            Migration::findOne();
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    private function getLastBatchId()
    {
        $lastBatchId = 0;

        try {
            $result = $this->connection->getQueryBuilder()->selectMax('batch_id')->table('ic_migrations')->whereEquals('status', MigrationStatus::COMPLETED)->get();
            $lastBatchId = (int) ($result->getFirstRecord()['max_value']);
        } catch (DatabaseException $e) {}
        
        return $lastBatchId;
    }

    /** @return Migration[] */
    private function getMigrationsToRollback($batchId = null)
    {
        $migrationParameters = new QueryParameters();
        $migrationParameters->in('status', [MigrationStatus::COMPLETED, MigrationStatus::IN_PROGESS]);
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
            $this->logger->warn($fileName . ' is not an instance of AbstractMigration or AbstractSeed. Skipping file');
            return;
        }

        if ($class->skipMigration)
        {
            $this->logger->warn($fileName . ' has skipMigration set to true. Skipping file');
        }
        else
        {
            $migration->batch_id = $this->currentBatchId;

            if ($this->migrationTableExists())
            {
                $migration->status = MigrationStatus::IN_PROGESS;
                $migration->save();
            }
            
            if ($class instanceof AbstractMigration)
            {
                $class->up();
            }
            else if ($class instanceof AbstractSeed)
            {
                $class->populate();
            }

            $migration->status = MigrationStatus::COMPLETED;
            $migration->save();
        }
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

        $migration->status = MigrationStatus::PENDING;
        $migration->save();
    }

}