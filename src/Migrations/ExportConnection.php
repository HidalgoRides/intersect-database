<?php

namespace Intersect\Database\Migrations;

use Intersect\Database\Query\Result;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Migrations\ExportQueryBuilder;

class ExportConnection extends Connection {

    private static $EXPORTABLE_ACTIONS = [
        'addColumn',
        'addForeignKey',
        'createIndex', 
        'createTable',
        'createTableIfNotExists',
        'dropColumns',
        'dropForeignKey',
        'dropIndex', 
        'dropTable', 
        'dropTableIfExists',
        'truncateTable',
        'insert', 
        'delete', 
        'update'
    ];
    
    private static $INSERT_ID_MAP = [];

    /** @var Connection */
    private $connection;

    /** @var ExportQueryBuilder */
    private $queryBuilder;

    private $queries = [];

    public function __construct(Connection $connection)
    {
        parent::__construct($connection->getConnectionSettings());
        $this->pdoDriver = $connection->getDriver();
        $this->connection = $connection;
        $this->queryBuilder = new ExportQueryBuilder($this, $this->connection->getQueryBuilder());
    }

    public function getQueries()
    {
        return $this->queries;
    }
 
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    public function switchDatabase($databaseName)
    {
        $this->connection->switchDatabase($databaseName);
    }

    public function query($sql, $bindParameters = [], $bypassCache = false)
    {
        $result = new Result();
        $action = $this->queryBuilder->getAction();

        if (!in_array($action, self::$EXPORTABLE_ACTIONS))
        {
            return $result;
        }

        if (count($bindParameters) > 0)
        {
            foreach ($bindParameters as $key => $value)
            {
                $sql = str_replace(':' . $key, "'" . addslashes($value) . "'", $sql);
            }
        }

        $this->queries[] = $sql;

        if ($this->queryBuilder->getAction() == 'insert')
        {
            $tableName = $this->queryBuilder->getTableName();

            if (!array_key_exists($tableName, self::$INSERT_ID_MAP))
            {
                self::$INSERT_ID_MAP[$tableName] = 1;
            }

            $result->setInsertId(self::$INSERT_ID_MAP[$tableName]);

            self::$INSERT_ID_MAP[$tableName]++;
        }

        return $result;
    }
    
}