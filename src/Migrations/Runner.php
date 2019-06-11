<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Logger\Logger;
use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Schema\SchemaBuilder;

class Runner {

    /** @var Connection */
    private $connection;

    /** @var FileStorage */
    private $fileStorage;

    /** @var Logger */
    private $logger;

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

    /**
     * @throws DatabaseException
     */
    public function run()
    {
        $this->checkForMigrationTable();

        $this->logger->info('Starting migration');

        $migrationPaths = $this->fileStorage->glob(rtrim($this->migrationDirectory, '/') . '/*_*.php');

        $filteredMigrationPaths = $this->filterMigrationPaths($migrationPaths);

        if (count($filteredMigrationPaths) == 0)
        {
            $this->logger->warn('Nothing to migrate!');
            return;
        }

        foreach ($filteredMigrationPaths as $path)
        {
            try {
                $this->migrate($path);
            } catch (\Exception $e) {
                $this->logger->error('An error occurred during migration of file: ' . $path);
                $this->logger->error(' * ' . $e->getMessage());
                return;
            }
        }

        $this->logger->info('Finished migration');
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
    private function filterMigrationPaths($migrationFiles)
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


    /**
     * @param $path
     * @throws DatabaseException
     * @throws \Intersect\Database\Exception\ValidationException
     */
    private function migrate($path)
    {
        $this->logger->info('Migrating file: ' . $path);

        $this->fileStorage->requireOnce($path);

        $className = MigrationHelper::resolveClassNameFromPath($path);
        $class = new $className();

        if (!$class instanceof AbstractMigration)
        {
            $this->logger->error($path . ' is not an instance of Migration. Skipping file');
            return;
        }

        SchemaBuilder::setConnection($this->connection);
        $class->setConnection($this->connection);

        $class->up();

        $pathParts = explode('/', $path);

        $migration = new Migration();
        $migration->name = end($pathParts);
        $migration->save();
    }

}