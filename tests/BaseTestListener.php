<?php

namespace Tests;

use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\TestListener;
use Intersect\Core\Logger\ConsoleLogger;
use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Connection\ConnectionRepository;
use Intersect\Database\Migrations\Runner;
use PHPUnit\Framework\TestListenerDefaultImplementation;

abstract class BaseTestListener implements TestListener {
    use TestListenerDefaultImplementation;

    /** @var Connection */
    protected $connection;
    
    private $databaseName = 'integration_tests';

    /** @var ConsoleLogger */
    private $logger;

    /** @var Runner */
    private $runner;

    /**
     * @return Connection
     */
    abstract protected function getConnection();
    abstract protected function testSuiteToHandle();

    public function startTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteToHandle())
        {
            $this->logger = new ConsoleLogger();
            $this->connection = $this->getConnection();

            ConnectionRepository::register($this->connection);
            ConnectionRepository::registerAlias('users');

            $this->logger->info('');
            $this->logger->info('Starting integration tests');
            $this->logger->info('');

            try {
                $this->createDatabase();

                $this->runner = new Runner($this->connection, new FileStorage(), $this->logger, [dirname(__FILE__) . '/Migrations/data/db-seed-data']);
                $this->runner->migrate(true);
            } catch (DatabaseException $e) {
                $this->logger->error($e->getMessage());
            }

            $this->logger->info('');
        }
    }

    public function endTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteToHandle())
        {   
            $this->logger->info('');
            $this->logger->info('');
            $this->dropDatabase();
        }
    }

    protected function createDatabase()
    {
        try {
            $this->logger->info('Creating database ' . $this->databaseName);
            $this->connection->getQueryBuilder()->createDatabase($this->databaseName)->get();
        } catch (DatabaseException $e) {
            $this->logger->error($e->getMessage());
        }
        
        try {
            $this->logger->info('Switching database to ' . $this->databaseName);
            $this->connection->switchDatabase($this->databaseName);
        } catch (DatabaseException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    protected function dropDatabase()
    {
        $this->logger->info('Dropping database ' . $this->databaseName);
        $this->connection->closeConnection();
        $this->connection->switchDatabase('app');
        $this->connection->getQueryBuilder()->dropDatabase($this->databaseName)->get();
    }

}