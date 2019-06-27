<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\ColumnDefinition;

class Blueprint {

    /** @var ColumnDefinition[] */
    private $columnDefinitions = [];

    /** @var Key[] */
    private $keys = [];

    private $tableName;

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /** @return ColumnDefinition[] */
    public function getColumnDefinitions()
    {
        return $this->columnDefinitions;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function datetime($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::DATETIME);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function numeric($name, $precision, $scale)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::NUMERIC);
        $columnDefinition->precision($precision);
        $columnDefinition->scale($scale);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function increments($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::INTEGER);
        $columnDefinition->primary();
        $columnDefinition->autoIncrement();

        $this->primary($name);
        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function tinyInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::TINY_INT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function smallInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::SMALL_INT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function mediumInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::MEDIUM_INT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function integer($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::INTEGER);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function bigInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::BIG_INT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function string($name, $length = 255)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::STRING);
        $columnDefinition->length($length);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function mediumText($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::MEDIUM_TEXT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function longText($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::LONG_TEXT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function text($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::TEXT);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function timestamp($name)
    {
        $columnDefinition = new ColumnDefinition($name, ColumnType::TIMESTAMP);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function unique($columnNames, $keyName = null)
    {
        $this->keys[] = new UniqueKey($this->tableName, $columnNames, $keyName);
    }

    public function primary($columnNames, $keyName = null)
    {
        $this->keys[] = new PrimaryKey($this->tableName, $columnNames, $keyName);
    }

    public function foreign($fromColumn, $toColumn, $onTable, $keyName = null)
    {
        $keyName = (!is_null($keyName) ? $keyName : $fromColumn . '_' . $onTable . '_' . $toColumn);

        $this->keys[] = new ForeignKey($keyName, $fromColumn, $toColumn, $onTable);
    }

    public function index($columns)
    {
        $this->keys[] = new Index($this->tableName, $columns);
    }

    /** @return Key[] */
    public function getKeys()
    {
        return $this->keys;
    }

    private function addColumnDefinition(ColumnDefinition $columnDefinition)
    {
        $this->columnDefinitions[] = $columnDefinition;
    }

}