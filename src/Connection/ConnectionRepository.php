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
    public static function get($key = 'default') 
    {
        $connection = null;

        if (array_key_exists($key, self::$CONNECTIONS))
        {
            $connection = self::$CONNECTIONS[$key];

            if ($connection instanceof \Closure)
            {
                $connection = $connection();
                self::$CONNECTIONS[$key] = $connection;
            }
        }
        else if (array_key_exists($key, self::$ALIASES))
        {
            $connection = self::get(self::$ALIASES[$key]);
        }

        return $connection;
    }

    /**
     * @param Connection|\Closure $connection
     * @param $key
     */
    public static function register($connection, $key = 'default') 
    {
        if (!$connection instanceof Connection && !$connection instanceof \Closure)
        {
            throw new \Exception('Cannot register connection for key "' . $key . '". Connection must be an instance of a "Connection" object or a "Closure" object');
        }
        
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