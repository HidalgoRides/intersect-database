<?php

namespace Tests;

use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;

class TestUtility {

    public static function getMySQLConnection($name = 'app')
    {
        $connectionSettings = ConnectionSettings::builder('db', 'root', 'password')->database($name)->port(3306)->build();
        return ConnectionFactory::get('mysql', $connectionSettings);
    }

    public static function getPostgresConnection($name = 'app')
    {
        $connectionSettings = ConnectionSettings::builder('db-postgres', 'root', 'password')->database($name)->schema('public')->port(5432)->build();
        return ConnectionFactory::get('pgsql', $connectionSettings);
    }

}