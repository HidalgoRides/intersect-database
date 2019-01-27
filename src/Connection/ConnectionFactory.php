<?php

namespace Intersect\Database\Connection;

class ConnectionFactory {

    /**
     * @param $driver
     * @param ConnectionSettings $connectionSettings
     * @return MySQLConnection|NullConnection|null
     * @throws \Intersect\Database\Exception\DatabaseException
     */
    public static function get($driver, ConnectionSettings $connectionSettings)
    {
        $connection = null;

        switch ($driver) {
            case 'mysql':
                $connection = new MySQLConnection($connectionSettings);
                break;
            default:
                $connection = new NullConnection($connectionSettings);
                break;
        }

        return $connection;
    }

}