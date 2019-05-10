<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Connection\Connection;

class ConnectionRepository {

    private static $ALIASES = [];
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

        if (array_key_exists($key, self::$ALIASES))
        {
            return self::get(self::$ALIASES[$key]);
        }

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

    public static function registerAlias($alias, $key = 'default')
    {
        self::$ALIASES[$alias] = $key;
    }

    public static function getConnections()
    {
        return self::$CONNECTIONS;
    }

}