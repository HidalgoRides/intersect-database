<?php

namespace Intersect\Database\Connection;

class ConnectionFactory {

    /**
     * @param $driver
     * @param ConnectionSettings $connectionSettings
     * @return MySQLConnection|PostgresConnection|NullConnection|null
     * @throws \Intersect\Database\Exception\DatabaseException
     */
    public static function get($driver, ConnectionSettings $connectionSettings)
    {
        $connection = null;

        switch ($driver) {
            case 'mysql':
                $connection = new MySQLConnection($connectionSettings);
                break;
            case 'pgsql':
                $connection = new PostgresConnection($connectionSettings);
                break;
            default:
                $connection = new NullConnection($connectionSettings);
                break;
        }

        return $connection;
    }

}