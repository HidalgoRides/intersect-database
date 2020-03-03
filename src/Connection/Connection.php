<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Query\Query;
use Intersect\Database\Query\Result;
use Intersect\Database\Query\Builder\QueryBuilder;
use PDOException;

abstract class Connection {

    private static $QUERY_CACHE = [];
    private static $RETRIEVAL_TOKENS = ['select', 'show'];
    private static $MODIFIED_TOKENS = ['insert', 'update', 'delete', 'truncate'];

    protected $pdoDriver = null;

    /** @var \PDO */
    private $connection;
    private $hash;

    /** @var ConnectionSettings */
    protected $connectionSettings;

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
     * @param $databaseName
     * @throws DatabaseException
     */
    abstract public function switchDatabase($databaseName);

    public function getDriver()
    {
        return $this->pdoDriver;
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

    public function closeConnection()
    {
        if (!is_null($this->connection))
        {
            $this->connection = null;
        }
    }

    /**
     * @param Query $query
     * @param boolean $bypassCache
     * @return Result
     * @throws DatabaseException
     */
    public function run(Query $query, $bypassCache = false)
    {
        return $this->query($query->getSql(), $query->getBindParameters(), $bypassCache);
    }

    /**
     * @param $sql
     * @param array $bindParameters
     * @param boolean $bypassCache
     * @return Result
     * @throws DatabaseException
     */
    public function query($sql, $bindParameters = [], $bypassCache = false)
    {
        $sql = trim($sql);

        $cacheString = $sql;
        foreach ($bindParameters as $key => $value)
        {
            $cacheString .= $key . $value;
        }

        $cacheKey = md5($this->hash . '#' . $cacheString);

        if (!$bypassCache && array_key_exists($cacheKey, self::$QUERY_CACHE))
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
                        if ($affectedRows > 0 || $token === 'truncate')
                        {
                            self::$QUERY_CACHE = [];
                        }

                        if ($token == 'insert')
                        {
                            try {
                                $result->setInsertId($this->getConnection()->lastInsertId());
                            } catch (PDOException $e) {}
                        }

                        break;
                    }
                }
            }

            $statement = null;
        }

        return $result;
    }

    public function startTransaction() 
    {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commitTransaction() 
    {
        return $this->getConnection()->commit();
    }
    
    public function rollbackTransaction() 
    {
        return $this->getConnection()->rollBack();
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

    /** @return ConnectionSettings */
    protected function getConnectionSettings()
    {
        return $this->connectionSettings;
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

        $this->hash = sha1($dsn . $connectionSettings->getUsername());

        try {
            $this->connection = new \PDO($dsn, $connectionSettings->getUsername(), $connectionSettings->getPassword(), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e);
        }
    }

}