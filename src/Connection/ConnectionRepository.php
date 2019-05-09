<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Connection\Connection;

class ConnectionRepository {

    private static $CONNECTIONS = [];

    private function __construct() {}

    /**
     * @param $key
     * @return Connection|null
     */
    public static function get($key) 
    {
        if (array_key_exists($key, self::$CONNECTIONS))
        {
            return self::$CONNECTIONS[$key];
        }

        // TODO: should this return NullConnection, throw an exception, or return null?
        return null;
    }

    /**
     * @param $key
     * @param Connection $connection
     */
    public static function register($key, Connection $connection) 
    {
        self::$CONNECTIONS[$key] = $connection;
    }

    public static function getConnections()
    {
        return self::$CONNECTIONS;
    }

    public static function clearConnections()
    {
        self::$CONNECTIONS = [];
    }

}