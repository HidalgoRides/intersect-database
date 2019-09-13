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

    /** @var FileStorage */
    private $fileStorage;

    /** @var Runner */
    private $runner;

    protected function setUp()
    {
        $this->connection = ConnectionRepository::get();
        $this->fileStorage = new FileStorage();
        $this->runner = new Runner($this->connection, $this->fileStorage, new NullLogger(), []);
    }

    public function test_export()
    {
        $migrationDirectory = dirname(__FILE__) . '/data/export-migrations';
        $this->runner->setMigrationDirectories([$migrationDirectory]);
        $exportedFilePath = $this->runner->export($migrationDirectory);

        if (!$this->fileStorage->fileExists($exportedFilePath))
        {
            $this->fail('exported file was not created successfully');
        }

        $exportedFile = $this->fileStorage->getFile($exportedFilePath);
        $lines = explode(PHP_EOL, $exportedFile);

        $this->fileStorage->deleteFile($exportedFilePath);
        $this->assertCount(15, $lines);
        
        if ($this->connection->getDriver() == 'mysql')
        {
            $this->assertTrue(strpos($lines[6], '-- File:') !== false);
            $this->assertEquals("create table `test_export_one` (`email` varchar(100) not null) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $lines[7]);
            $this->assertTrue(strpos($lines[9], '-- File:') !== false);
            $this->assertEquals("create table `test_export_two` (`email` varchar(100) not null) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $lines[10]);
            $this->assertEquals("alter table `test_export_two` add constraint fk_to_drop foreign key (`from_column`) references `on_table` (`to_column`);", $lines[11]);
            $this->assertEquals("alter table `test_export_two` drop foreign key fk_to_drop;", $lines[12]);
        }
        else if ($this->connection->getDriver() == 'pgsql')
        {
            $this->assertTrue(strpos($lines[6], '-- File:') !== false);
            $this->assertEquals("create table public.test_export_one (email varchar(100) not null);", $lines[7]);
            $this->assertTrue(strpos($lines[9], '-- File:') !== false);
            $this->assertEquals("create table public.test_export_two (email varchar(100) not null);", $lines[10]);
            $this->assertEquals("alter table public.test_export_two add constraint fk_to_drop foreign key (from_column) references public.on_table (to_column);", $lines[11]);
            $this->assertEquals("alter table public.test_export_two drop constraint fk_to_drop;", $lines[12]);
        }
    }

    public function test_export_withSeedData()
    {
        $migrationDirectory = dirname(__FILE__) . '/data/export-migrations';
        $this->runner->setMigrationDirectories([$migrationDirectory]);
        $exportedFilePath = $this->runner->export($migrationDirectory, true);

        if (!$this->fileStorage->fileExists($exportedFilePath))
        {
            $this->fail('exported file was not created successfully');
        }

        $exportedFile = $this->fileStorage->getFile($exportedFilePath);
        $lines = explode(PHP_EOL, $exportedFile);

        $this->fileStorage->deleteFile($exportedFilePath);

        $this->assertCount(15, $lines);

        if ($this->connection->getDriver() == 'mysql')
        {
            $this->assertTrue(strpos($lines[6], '-- File:') !== false);
            $this->assertEquals("create table `test_export_one` (`email` varchar(100) not null) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $lines[7]);
            $this->assertTrue(strpos($lines[9], '-- File:') !== false);
            $this->assertEquals("create table `test_export_two` (`email` varchar(100) not null) engine=InnoDB charset=utf8 collate=utf8_unicode_ci;", $lines[10]);
            $this->assertEquals("alter table `test_export_two` add constraint fk_to_drop foreign key (`from_column`) references `on_table` (`to_column`);", $lines[11]);
            $this->assertEquals("alter table `test_export_two` drop foreign key fk_to_drop;", $lines[12]);
        }
        else if ($this->connection->getDriver() == 'pgsql')
        {
            $this->assertTrue(strpos($lines[6], '-- File:') !== false);
            $this->assertEquals("create table public.test_export_one (email varchar(100) not null);", $lines[7]);
            $this->assertTrue(strpos($lines[9], '-- File:') !== false);
            $this->assertEquals("create table public.test_export_two (email varchar(100) not null);", $lines[10]);
            $this->assertEquals("alter table public.test_export_two add constraint fk_to_drop foreign key (from_column) references public.on_table (to_column);", $lines[11]);
            $this->assertEquals("alter table public.test_export_two drop constraint fk_to_drop;", $lines[12]);
        }
        
    }

    public function test_migrate_singleMigration()
    {
        $migrationDirectory = dirname(__FILE__) . '/data/single-migration';
        $this->runner->setMigrationDirectories([$migrationDirectory]);
        $this->runner->migrate();

        $this->assertTableMigrated('test_migration_one');
    }

    public function test_migrate_multipleMigrations()
    {
        $migrationDirectory = dirname(__FILE__) . '/data/multiple-migrations';
        $this->runner->setMigrationDirectories([$migrationDirectory]);
        $this->runner->migrate();

        $this->assertTableMigrated('test_migration_two');
        $this->assertTableMigrated('test_migration_three');
    }

    public function test_migrate_multipleMigrationSources_verifyRanInChronologicalOrder()
    {
        $migrationDirectory1 = dirname(__FILE__) . '/data/multiple-migration-sources/source1';
        $migrationDirectory2 = dirname(__FILE__) . '/data/multiple-migration-sources/source2';
        $this->runner->setMigrationDirectories([$migrationDirectory1, $migrationDirectory2]);
        $this->runner->migrate(true);

        $this->assertTableMigrated('test_migration_source_1');
        $this->assertTableMigrated('test_migration_source_2');
    }

    public function test_migrate_rollbackMigrations()
    {
        $migrationDirectory = dirname(__FILE__) . '/data/rollback-migrations';
        $this->runner->setMigrationDirectories([$migrationDirectory]);
        $this->runner->migrate();

        $this->assertTableMigrated('test_migration_four');
        $this->assertTableMigrated('test_migration_five');

        $this->runner->rollbackLastBatch();

        $this->assertTableRolledBack('test_migration_four');
        $this->assertTableRolledBack('test_migration_five');
    }

    public function test_migrate_rollbackOnException()
    {
        $migrationDirectory = dirname(__FILE__) . '/data/rollback-migration-exception';
        $this->runner->setMigrationDirectories([$migrationDirectory]);
        $this->runner->migrate();

        $this->assertTableRolledBack('test_migration_rollback');
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
            $this->connection->getQueryBuilder()->select()->table($tableName)->get();
        } catch (DatabaseException $e) {
            $this->assertTrue((strpos($e->getMessage(), 'Undefined table') > -1) || (strpos($e->getMessage(), 'Base table or view not found') > -1));
            return;
        }

        $this->fail('Runner should have rolled back table "' . $tableName . '"');
    }

}