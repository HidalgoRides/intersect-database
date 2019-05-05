<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Connection\ConnectionSettings;
use Intersect\Database\Query\Builder\MySQLQueryBuilder;

class MySQLConnection extends Connection {

    protected $pdoDriver = 'mysql';

    public function getQueryBuilder()
    {
        return new MySQLQueryBuilder($this);
    }

    protected function buildDsnMap(ConnectionSettings $connectionSettings)
    {
        $dsnMap = parent::buildDsnMap($connectionSettings);

        $dsnMap['charset'] = $connectionSettings->getCharset();
        
        return $dsnMap;
    }

}