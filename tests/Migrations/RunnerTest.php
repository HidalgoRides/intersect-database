<?php

namespace Tests\Migrations;

use PHPUnit\Framework\TestCase;
use Intersect\Core\Logger\NullLogger;
use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Migrations\Runner;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Connection\ConnectionRepository;

class RunnerTest extends TestCase {

    /** @var Connection */
    private $connection;

    /** @var Runner */
    private $runner;

    protected function setUp()
    {
        $this->connection = ConnectionRepository::get();
        $this->runner = new Runner($this->connection, new FileStorage(), new NullLogger(), '/');
    }

    public function test_run_singleMigration()
    {
        $this->runner->setMigrationDirectory(dirname(__FILE__) . '/data/single-migration');
        $this->runner->migrate();

        $this->assertTableMigrated('test_migration_one');
    }

    public function test_run_multipleMigrations()
    {
        $this->runner->setMigrationDirectory(dirname(__FILE__) . '/data/multiple-migrations');
        $this->runner->migrate();

        $this->assertTableMigrated('test_migration_two');
        $this->assertTableMigrated('test_migration_three');
    }

    public function test_run_rollbackMigrations()
    {
        $this->runner->setMigrationDirectory(dirname(__FILE__) . '/data/rollback-migrations');
        $this->runner->migrate();

        $this->assertTableMigrated('test_migration_four');
        $this->assertTableMigrated('test_migration_five');

        $this->runner->rollbackLastBatch();

        $this->assertTableRolledBack('test_migration_four');
        $this->assertTableRolledBack('test_migration_five');
    }

    private function assertTableMigrated($tableName)
    {
        try {
            $result = $this->connection->getQueryBuilder()->select()->table($tableName)->get();
            $this->assertNotNull($result);
        } catch (DatabaseException $e) {
            $this->fail('Runner should have migrated table "' . $tableName . '" - ' . $e->getMessage());
        }
    }

    private function assertTableRolledBack($tableName)
    {
        try {
            $result = $this->connection->getQueryBuilder()->select()->table($tableName)->get();
            $this->assertNotNull($result);
        } catch (DatabaseException $e) {
            return;
        }

        $this->fail('Runner should have rolled back table "' . $tableName . '"');
    }

}