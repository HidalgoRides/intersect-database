<?php

namespace Tests;

use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;

class PostgresTestListener extends BaseTestListener {

    protected function getConnection()
    {
        $connectionSettings = new ConnectionSettings('db-postgres', 'root', 'password', 5432, 'app');
        return ConnectionFactory::get('pgsql', $connectionSettings);
    }

    protected function testSuiteToHandle()
    {
        return 'postgres';
    }

    protected function createDatabaseQuery($name)
    {
        return 'CREATE DATABASE ' . $name;
    }

    protected function createSchemaQueries()
    {
        $queries = [];

        $queries[] = "CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(50) NOT NULL,
            phone_id INT,
            name_id INT,
            meta_data TEXT,
            date_created TIMESTAMP,
            date_updated TIMESTAMP
        );";
          
        $queries[] = "CREATE TABLE phones (
            id SERIAL PRIMARY KEY,
            number VARCHAR(15) NOT NULL
        );";
          
        $queries[] = "CREATE TABLE names (
            id SERIAL PRIMARY KEY,
            first_name VARCHAR(20) NOT NULL,
            last_name VARCHAR(20) NOT NULL
        );";

        $queries[] = "CREATE TABLE addresses (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL,
            street VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(5) NOT NULL,
            zip_code VARCHAR(20) NOT NULL
        );";

        $queries[] = "CREATE TABLE associations (
            association_id INT DEFAULT NULL,
            key_one INT NOT NULL,
            key_two INT NOT NULL,
            PRIMARY KEY (key_one, key_two)
        );";

        return $queries;
    }

    protected function createDataQueries()
    {
        $queries = [];

        $queries[] = "INSERT INTO names (first_name, last_name) VALUES ('Unit', 'Test');";
        $queries[] = "INSERT INTO phones (number) VALUES ('15551234567');";
        $queries[] = "INSERT INTO users (email, phone_id, name_id) VALUES ('unit-test@test.com', 1, 1);";
        $queries[] = "INSERT INTO addresses (user_id, street, city, state, zip_code) VALUES (1, 'Test Street 1', 'Test City 1', 'IT', '12345');";
        $queries[] = "INSERT INTO addresses (user_id, street, city, state, zip_code) VALUES (1, 'Test Street 2', 'Test City 2', 'IT', '67890');";
        $queries[] = "INSERT INTO associations (key_one, key_two) VALUES (1, 1);";
        $queries[] = "INSERT INTO associations (key_one, key_two) VALUES (1, 2);";
        $queries[] = "INSERT INTO associations (key_one, key_two) VALUES (2, 1);";

        return $queries;
    }

    protected function dropDatabase()
    {
        // switch connections before dropping database
        $postgresConnectionSettings = new ConnectionSettings('db-postgres', 'root', 'password', 5432, 'app');
        $this->connection = ConnectionFactory::get('pgsql', $postgresConnectionSettings);

        parent::dropDatabase();
    }

    protected function dropDatabaseQuery($name)
    {
        return 'DROP DATABASE ' . $name;
    }

}