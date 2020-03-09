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

}