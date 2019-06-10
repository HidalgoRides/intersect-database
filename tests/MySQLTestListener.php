<?php

namespace Tests;

class MySQLTestListener extends BaseTestListener {

    protected function getConnection()
    {
        return TestUtility::getMySQLConnection();
    }

    protected function testSuiteToHandle()
    {
        return 'mysql';
    }

    protected function createDatabaseQuery($name)
    {
        return 'CREATE DATABASE IF NOT EXISTS ' . $name;
    }

    protected function createSchemaQueries()
    {
        $queries = [];

        $queries[] = "CREATE TABLE users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(50) NOT NULL,
            phone_id INT(11),
            name_id INT(11),
            meta_data TEXT,
            date_created DATETIME,
            date_updated DATETIME
          );";
          
        $queries[] = "CREATE TABLE phones (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            number VARCHAR(15) NOT NULL
          );";
          
        $queries[] = "CREATE TABLE names (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(20) NOT NULL,
            last_name VARCHAR(20) NOT NULL
          );";

        $queries[] = "CREATE TABLE addresses (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            street VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(5) NOT NULL,
            zip_code VARCHAR(20) NOT NULL
          );";

        $queries[] = "CREATE TABLE associations (
            association_id INT(11) DEFAULT NULL,
            key_one INT(11) NOT NULL,
            key_two INT(11) NOT NULL,
            data VARCHAR(100),
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

    protected function dropDatabaseQuery($name)
    {
        return 'DROP DATABASE IF EXISTS ' . $name;
    }

}