<?php

namespace Tests;

class PostgresTestListener extends BaseTestListener {

    protected function getConnection()
    {
        return TestUtility::getPostgresConnection();
    }

    protected function testSuiteToHandle()
    {
        return 'postgres';
    }

}