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
use Intersect\Database\Connection\ConnectionRepository;

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
        $connectionSettings = ConnectionSettings::builder('db', 'root', 'password')->database('app')->port(3306)->build();

        $this->connection = ConnectionFactory::get('mysql', $connectionSettings);
        $this->logger = new ConsoleLogger();

        ConnectionRepository::register($this->connection);
        // registered 'users' connection to simulate models having different connections than default
        ConnectionRepository::register($this->connection, 'users');
    }

    public function startTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteName)
        {
            $this->logger->info('');
            $this->logger->info('Starting integration tests');
            $this->logger->info('');

            try {
                $this->initTestDatabase();
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
            $this->dropDatabase();
        }
    }

    private function initTestDatabase()
    {
        $this->logger->info('Creating database ' . $this->databaseName);
        $this->connection->query('CREATE DATABASE IF NOT EXISTS ' . $this->databaseName);

        $this->logger->info('Switching database to ' . $this->databaseName);
        $this->connection->switchDatabase($this->databaseName);

        $this->logger->info('Applying test database schemas and data');
        $this->connection->query("CREATE TABLE users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(50) NOT NULL,
            phone_id INT(11),
            name_id INT(11),
            meta_data TEXT,
            date_created DATETIME,
            date_updated DATETIME
          );
          
          CREATE TABLE phones (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            number VARCHAR(15) NOT NULL
          );
          
          CREATE TABLE names (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(20) NOT NULL,
            last_name VARCHAR(20) NOT NULL
          );

          CREATE TABLE addresses (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            street VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(5) NOT NULL,
            zip_code VARCHAR(20) NOT NULL
          );

          CREATE TABLE associations (
            association_id INT(11) DEFAULT NULL,
            key_one INT(11) NOT NULL,
            key_two INT(11) NOT NULL,
            PRIMARY KEY (key_one, key_two)
          );
          
          INSERT INTO names (first_name, last_name) VALUES ('Unit', 'Test');
          INSERT INTO phones (number) VALUES ('15551234567');
          INSERT INTO users (email, phone_id, name_id) VALUES ('unit-test@test.com', 1, 1);

          INSERT INTO addresses (user_id, street, city, state, zip_code) VALUES (1, 'Test Street 1', 'Test City 1', 'IT', '12345');
          INSERT INTO addresses (user_id, street, city, state, zip_code) VALUES (1, 'Test Street 2', 'Test City 2', 'IT', '67890');

          INSERT INTO associations (key_one, key_two) VALUES (1, 1);
          INSERT INTO associations (key_one, key_two) VALUES (1, 2);
          INSERT INTO associations (key_one, key_two) VALUES (2, 1);
        ");
    }

    private function dropDatabase()
    {
        $this->logger->info('Dropping database ' . $this->databaseName);
        $this->connection->query('DROP DATABASE IF EXISTS ' . $this->databaseName);
    }

}