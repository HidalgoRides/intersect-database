<?php

namespace Intersect\Database\Schema;

use Closure;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Connection\Connection;

class SchemaBuilder {

    /** @var Connection */
    private static $CONNECTION;

    private function __construct() {}

    public static function setConnection(Connection $connection)
    {
        self::$CONNECTION = $connection;
    }

    public static function createTable($tableName, Closure $closure)
    {
        $blueprint = new Blueprint($tableName);

        $closure($blueprint);

        self::$CONNECTION->getQueryBuilder()->createTable($blueprint)->get();
    }

    public static function dropTable($tableName)
    {
        self::$CONNECTION->getQueryBuilder()->dropTable($tableName)->get();
    }

    public static function dropTableIfExists($tableName)
    {
        self::$CONNECTION->getQueryBuilder()->dropTableIfExists($tableName)->get();
    }

}