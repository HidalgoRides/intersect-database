<?php

namespace Tests;

use PHPUnit\Framework\TestSuite;
use Intersect\Database\Model\Model;
use PHPUnit\Framework\TestListener;
use Intersect\Core\Logger\ConsoleLogger;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;
use PHPUnit\Framework\TestListenerDefaultImplementation;

class IntegrationTestListener implements TestListener {
    use TestListenerDefaultImplementation;

    /** @var Connection */
    private $connection;

    /** @var ConsoleLogger */
    private $logger;
    private $databaseName = 'integration_tests';
    private $testSuiteName = 'Intersect Database Tests';

    public function __construct()
    {
        $connectionSettings = new ConnectionSettings('db', 'root', 'password', 3306, 'app');
        $this->connection = ConnectionFactory::get('mysql', $connectionSettings);
        $this->logger = new ConsoleLogger();

        Model::setConnection($this->connection);
    }

    public function startTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteName)
        {
            $this->logger->info('');
            $this->logger->info('Starting integration tests');
            $this->logger->info('');

            $this->createDatabaseAndUse();

            try {
                $this->createTestsTable();
                $this->createRelationalTestTables();
            } catch (DatabaseException $e) {}

            $this->logger->info('');
        }
    }

    public function endTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteName)
        {
            $this->logger->info('');
            $this->logger->info('');
            $this->logger->info('Ending test suite');

            $this->dropDatabase();
        }
    }

    private function createDatabaseAndUse()
    {
        $this->logger->info('Creating database ' . $this->databaseName);
        $this->connection->query('CREATE DATABASE IF NOT EXISTS ' . $this->databaseName);

        $this->logger->info('Switching database to ' . $this->databaseName);
        $this->connection->switchDatabase($this->databaseName);
    }

    private function createTestsTable()
    {
        $this->connection->query("CREATE TABLE tests (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            data VARCHAR(255),
            foo_bar VARCHAR(255)
        )");

        $this->connection->query("INSERT INTO tests (data, foo_bar) VALUES ('data', 'test')");
    }

    private function createRelationalTestTables()
    {
        $this->connection->query("CREATE TABLE test_relation_one (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            data_id VARCHAR(255)
        )");

        $this->connection->query("CREATE TABLE test_relation_two (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255)
        )");

        $this->connection->query("INSERT INTO test_relation_one (data_id) VALUES (1)");
        $this->connection->query("INSERT INTO test_relation_two (title) VALUES ('data title')");
    }

    private function dropDatabase()
    {
        $this->logger->info('Dropping database ' . $this->databaseName);
        $this->connection->query('DROP DATABASE IF EXISTS ' . $this->databaseName);
    }

}