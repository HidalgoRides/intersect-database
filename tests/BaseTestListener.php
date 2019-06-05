<?php

namespace Tests;

use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\TestListener;
use Intersect\Core\Logger\ConsoleLogger;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Connection\ConnectionRepository;
use PHPUnit\Framework\TestListenerDefaultImplementation;

abstract class BaseTestListener implements TestListener {
    use TestListenerDefaultImplementation;

    /** @var Connection */
    protected $connection;

    /** @var ConsoleLogger */
    private $logger;
    private $databaseName = 'integration_tests';

    /**
     * @return Connection
     */
    abstract protected function getConnection();
    abstract protected function testSuiteToHandle();
    abstract protected function createDatabaseQuery($name);
    abstract protected function createSchemaQueries();
    abstract protected function createDataQueries();
    abstract protected function dropDatabaseQuery($name);

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
                $this->initDatabase();
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

    protected function initDatabase()
    {
        try {
            $this->logger->info('Creating database ' . $this->databaseName);
            $this->connection->query($this->createDatabaseQuery($this->databaseName));
        } catch (DatabaseException $e) {
            $this->logger->error($e->getMessage());
        }
        
        try {
            $this->logger->info('Switching database to ' . $this->databaseName);
            $this->connection->switchDatabase($this->databaseName);

            $this->logger->info('Applying database schemas');
            
            $queries = $this->createSchemaQueries();
            foreach ($queries as $query)
            {
                $this->connection->query($query);
            }

            $this->logger->info('Applying database data');

            $queries = $this->createDataQueries();
            foreach ($queries as $query)
            {
                $this->connection->query($query);
            }
        } catch (DatabaseException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    protected function dropDatabase()
    {
        $this->logger->info('Dropping database ' . $this->databaseName);

        $query = $this->dropDatabaseQuery($this->databaseName);

        $this->connection->closeConnection();

        $this->connection->query($query);
    }

}