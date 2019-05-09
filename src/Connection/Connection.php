<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Query\Query;
use Intersect\Database\Query\Result;

abstract class Connection {

    private static $QUERY_CACHE = [];
    private static $RETRIEVAL_TOKENS = ['select', 'show'];
    private static $MODIFIED_TOKENS = ['insert', 'update', 'delete'];

    protected $pdoDriver = null;

    /** @var \PDO */
    private $connection;

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
     * @return \PDO
     * @throws DatabaseException
     */
    public function getConnection()
    {
        if (is_null($this->connection))
        {       
            $this->initConnection($this->connectionSettings);
        }

        return $this->connection;
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

        $cacheKey = md5($cacheString);

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
            $result->setInsertId($this->getConnection()->lastInsertId());

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
            }
        }

        return $result;
    }

    /**
     * @param $host
     * @param $databaseName
     * @param $port
     * @return string
     */
    private function buildDsn($host, $databaseName, $port)
    {
        $dsn = $this->pdoDriver . ':host=' . $host . ';port=' . $port . ';charset=utf8;';

        if (trim($databaseName) != '')
        {
            $dsn .= 'dbname=' . $databaseName;
        }

        return $dsn;
    }

    /**
     * @param ConnectionSettings $connectionSettings
     * @throws DatabaseException
     */
    private function initConnection(ConnectionSettings $connectionSettings)
    {
        $dsn = $this->buildDsn($connectionSettings->getHost(), $connectionSettings->getDatabase(), $connectionSettings->getPort());

        try {
            $this->connection = new \PDO($dsn, $connectionSettings->getUsername(), $connectionSettings->getPassword(), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e);
        }
    }

}