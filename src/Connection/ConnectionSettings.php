<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Connection\ConnectionSettingsBuilder;

class ConnectionSettings {

    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $charset = 'utf8';
    private $schema;

    public function __construct() {}

    /**
     * @param $host
     * @param $username
     * @param $password
     * @return ConnectionSettingsBuilder
     */
    public static function builder($host, $username, $password) 
    {
        return new ConnectionSettingsBuilder($host, $username, $password);
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

}