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
     * @param Connection $connection
     * @param $key
     */
    public static function register(Connection $connection, $key = 'default') 
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