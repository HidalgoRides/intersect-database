<?php

namespace Intersect\Database\Connection;

class ConnectionSettings {

    private $host;
    private $username;
    private $password;
    private $database;
    private $port;

    public function __construct($host, $username, $password, $port, $database)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->database = $database;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

}