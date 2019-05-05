<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Connection\ConnectionSettings;

class MySQLConnection extends Connection {

    protected $pdoDriver = 'mysql';

    protected function buildDsnMap(ConnectionSettings $connectionSettings)
    {
        $dsnMap = parent::buildDsnMap($connectionSettings);

        $dsnMap['charset'] = $connectionSettings->getCharset();
        
        return $dsnMap;
    }

}