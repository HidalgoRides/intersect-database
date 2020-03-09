<?php

namespace Intersect\Database\Connection;

use Intersect\Database\Connection\ConnectionSettings;

class ConnectionSettingsBuilder {

    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $charset = 'utf8';
    private $schema;

    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param $database
     * @return ConnectionSettingsBuilder
     */
    public function database($database) 
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @param $port
     * @return ConnectionSettingsBuilder
     */
    public function port($port) 
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param $charset
     * @return ConnectionSettingsBuilder
     */
    public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @param $schema
     * @return ConnectionSettingsBuilder
     */
    public function schema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    public function build() 
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setHost($this->host);
        $connectionSettings->setUsername($this->username);
        $connectionSettings->setPassword($this->password);
        $connectionSettings->setDatabase($this->database);
        $connectionSettings->setPort($this->port);
        $connectionSettings->setCharset($this->charset);
        $connectionSettings->setSchema($this->schema);
        return $connectionSettings;
    }

}