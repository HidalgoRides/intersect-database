<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\Result;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Exception\DatabaseException;

abstract class Connection implements ConnectionInterface {

    private static $QUERY_CACHE = [];
    private static $RETRIEVAL_TOKENS = ['select', 'show'];
    private static $MODIFIED_TOKENS = ['insert', 'update', 'delete'];

    /** @var \PDO[] */
    private static $CONNECTIONS = [];

    protected $pdoDriver;

    /** @var ConnectionSettings */
    private $connectionSettings;

    /**
     * Connection constructor.
     * @param ConnectionSettings $connectionSettings
     * @throws DatabaseException
     */
    public function __construct(ConnectionSettings $connectionSettings)
    {
        $this->connectionSettings = $connectionSettings;
    }

    /**
     * @return QueryBuilder
     */
    abstract public function getQueryBuilder();

    /**
     * @return \PDO
     * @throws DatabaseException
     */
    public function getConnection()
    {
        if (!array_key_exists($this->pdoDriver, self::$CONNECTIONS))
        {
            $this->initConnection($this->connectionSettings);
        }

        return self::$CONNECTIONS[$this->pdoDriver];
    }

    /**
     * @param $databaseName
     * @throws DatabaseException
     */
    public function switchDatabase($databaseName)
    {
        $this->getConnection()->exec('use ' . $databaseName);
    }

    /**
     * @param Query $query
     * @return Result
     * @throws DatabaseException
     */
    public function run(Query $query)
    {
        return $this->query($query->getSql(), $query->getBindParameters());
    }

    /**
     * @param $sql
     * @param array $bindParameters
     * @return Result
     * @throws DatabaseException
     */
    public function query($sql, $bindParameters = [])
    {
        $sql = trim($sql);

        $cacheString = $sql;
        foreach ($bindParameters as $key => $value)
        {
            $cacheString .= $key . $value;
        }

        $cacheKey = md5($this->pdoDriver . '#' . $cacheString);

       if (array_key_exists($cacheKey, self::$QUERY_CACHE))
       {
           return self::$QUERY_CACHE[$cacheKey];
       }

        $result = new Result();
        $statement = $this->getConnection()->prepare($sql);

        try {
            $statement->execute($bindParameters);
        } catch (\Exception $e) {
            throw new DatabaseException($e->getMessage());
        }

        if (is_null($statement))
        {
            throw new DatabaseException('Something went wrong executing query.');
        }

        if ($statement)
        {
            $affectedRows = $statement->rowCount();
            $result->setAffectedRows($affectedRows);

            $recordsRetrieved = false;

            foreach (self::$RETRIEVAL_TOKENS as $token)
            {
                if (stripos($sql, $token) === 0)
                {
                    $recordsRetrieved = true;

                    $records = $statement->fetchAll(\PDO::FETCH_ASSOC);
                    $result->setRecords($records);

                    self::$QUERY_CACHE[$cacheKey] = $result;
                    break;
                }
            }

            if (!$recordsRetrieved)
            {
                foreach (self::$MODIFIED_TOKENS as $token)
                {
                    if (stripos($sql, $token) === 0)
                    {
                        if ($affectedRows > 0)
                        {
                            self::$QUERY_CACHE = [];
                        }

                        break;
                    }
                }

                $result->setInsertId($this->getConnection()->lastInsertId());
            }
        }

        return $result;
    }

    protected function buildDsnMap(ConnectionSettings $connectionSettings)
    {
        $map = [
            'host' => $connectionSettings->getHost(),
            'port' => $connectionSettings->getPort()
        ];

        $databaseName = $connectionSettings->getDatabase();

        if (trim($databaseName) != '')
        {
            $map['dbname'] = $databaseName;
        }

        return $map;
    }

    /**
     * @param $host
     * @param $databaseName
     * @param $port
     * @return string
     */
    private function buildDsn(ConnectionSettings $connectionSettings)
    {
        $dsn = $this->pdoDriver . ':';

        $dsnMap = $this->buildDsnMap($connectionSettings);
        foreach ($dsnMap as $key => $value)
        {
            $dsn .= $key . '=' . $value . ';';
        }

        return $dsn;
    }

    /**
     * @param ConnectionSettings $connectionSettings
     * @throws DatabaseException
     */
    private function initConnection(ConnectionSettings $connectionSettings)
    {
        $dsn = $this->buildDsn($connectionSettings);

        try {
            self::$CONNECTIONS[$this->pdoDriver] = new \PDO($dsn, $connectionSettings->getUsername(), $connectionSettings->getPassword(), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e);
        }
    }

}