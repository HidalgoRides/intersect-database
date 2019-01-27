<?php

namespace Intersect\Database\Schema;

class Table {

    private $charset = 'utf8';
    /** @var Column[]  */
    private $columns = [];
    private $engine = 'InnoDB';
    private $keys = [];
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param Column $column
     */
    public function addColumn(Column $column)
    {
        $this->columns[] = $column;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    public function getKeys()
    {
        return $this->keys;
    }

    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }

    public function getName()
    {
        return $this->name;
    }

}