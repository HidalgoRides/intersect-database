<?php

namespace Tests;

use PHPUnit\Framework\TestSuite;
use Intersect\Database\Model\Model;
use PHPUnit\Framework\TestListener;
use Intersect\Core\Logger\ConsoleLogger;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Connection\NullConnection;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;
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
            Model::setConnection($this->connection);

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
            Model::setConnection(new NullConnection());

            $this->logger->info('');
            $this->logger->info('');
            $this->dropDatabase();
        }
    }

    protected function initDatabase()
    {
        $this->logger->info('Creating database ' . $this->databaseName);
        $this->connection->query($this->createDatabaseQuery($this->databaseName));

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
    }

    protected function dropDatabase()
    {
        $this->logger->info('Dropping database ' . $this->databaseName);

        $query = $this->dropDatabaseQuery($this->databaseName);

        $this->connection->closeConnection();

        $this->connection->query($query);
    }

}