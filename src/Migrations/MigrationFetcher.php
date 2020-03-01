<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Migrations\Migration;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Migrations\AbstractSeed;
use Intersect\Database\Exception\DatabaseException;

class MigrationFetcher {

    /** @var Connection */
    private $connection;

    /** @var FileStorage */
    private $fileStorage;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->fileStorage = new FileStorage();
    }

    /**
     * returns a sorted list of all migrations to run with seed data being at the end of the list (if included)
     * 
     * @return Migration[]
     */
    public function fetch(array $directories = [], $includedSeeds = false, $ignoreExistingMigrations = false)
    {
        // TODO: extend method parameters to accept flag to bypass delta diff with already applied migrations

        // TODO: return a sorted list of all migrations to run
        // Order:
        //   1) all migrations that extend AbstractMigration
        //   2) all seeds that extend AbstractSeed

        $allMigrations = ['migrations' => [], 'seeds' => []];
        $existingMigrationsFromDB = (!$ignoreExistingMigrations ? $this->getExistingMigrationsFromDB() : []);

        foreach ($directories as $directory)
        {
            $paths = $this->fileStorage->glob(rtrim($directory, '/') . '/*_*.php');
            
            if (count($paths) == 0) continue;

            foreach ($paths as $path)
            {
                $this->fileStorage->requireOnce($path);

                $className = MigrationHelper::resolveClassNameFromPath($path);
                $class = new $className($this->connection);
                $isSeedFile = ($class instanceof AbstractSeed);

                if ($isSeedFile && !$includedSeeds)
                {
                    continue;
                }

                $pathParts = explode('/', $path);
                $fileName = end($pathParts);
                $hashedFileName = md5($fileName);
                $migration = null;

                if (!$ignoreExistingMigrations && array_key_exists($hashedFileName, $existingMigrationsFromDB))
                {
                    $existingMigration = $existingMigrationsFromDB[$hashedFileName];
                    if ($existingMigration->status != MigrationStatus::COMPLETED)
                    {
                        $migration = $existingMigration;
                    }
                }
                else
                {
                    $migration = new Migration();
                    $migration->name = $fileName;
                    $migration->path = $path;
                }

                if (!is_null($migration))
                {
                    if ($isSeedFile)
                    {
                        $allMigrations['seeds'][] = $migration;
                    }
                    else
                    {
                        $allMigrations['migrations'][] = $migration;
                    }
                }
            }
        }

        $this->sortMigrations($allMigrations['migrations']);
        $this->sortMigrations($allMigrations['seeds']);

        return array_merge($allMigrations['migrations'], $allMigrations['seeds']);
    }

    /**
     * returns an array all migrations in the database
     * 
     *  - key is hashed value of migration "name"
     *  - value is the migration model
     * 
     * @return Migration[]
     */
    private function getExistingMigrationsFromDB()
    {
        $existingMigrationsFromDB = [];

        try {
            $existingMigrations = Migration::find();

            foreach ($existingMigrations as $existingMigration)
            {
                $existingMigrationsFromDB[md5($existingMigration->name)] = $existingMigration;
            }
        } catch (DatabaseException $de) {}

        return $existingMigrationsFromDB;
    }

    private function getTimeFromMigrationPath($migrationPath)
    {
        $migrationPathParts = explode('/', str_replace('.php', '', $migrationPath));
        $migrationPathParts = explode('_', end($migrationPathParts));
        return end($migrationPathParts);
    }

    private function sortMigrations(array &$migrations)
    {
        usort($migrations, function($m1, $m2) {
            return strcmp($this->getTimeFromMigrationPath($m1->path), $this->getTimeFromMigrationPath($m2->name));
        });
    }

}