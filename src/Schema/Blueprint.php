<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\Key\UniqueKey;

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
        $columnDefinition = new ColumnDefinition($name, 'datetime');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function numeric($name, $precision, $scale)
    {
        $columnDefinition = new ColumnDefinition($name, 'numeric');
        $columnDefinition->precision($precision);
        $columnDefinition->scale($scale);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function increments($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'integer');
        $columnDefinition->primary();
        $columnDefinition->autoIncrement();

        $this->primary($name);
        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function tinyInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'tinyint');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function smallInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'smallint');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function mediumInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'mediumint');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function integer($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'integer');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function bigInteger($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'bigint');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function string($name, $length = 255)
    {
        $columnDefinition = new ColumnDefinition($name, 'string');
        $columnDefinition->length($length);

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function mediumText($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'mediumtext');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function longText($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'longtext');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function text($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'text');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function timestamp($name)
    {
        $columnDefinition = new ColumnDefinition($name, 'timestamp');

        $this->addColumnDefinition($columnDefinition);

        return $columnDefinition;
    }

    public function unique($columnNames, $keyName = null)
    {
        $this->keys[] = new UniqueKey($columnNames, $keyName);
    }

    public function primary($columnNames, $keyName = null)
    {
        $this->keys[] = new PrimaryKey($columnNames, $keyName);
    }

    public function foreign($fromColumn, $toColumn, $onTable, $keyName = null)
    {
        $keyName = (!is_null($keyName) ? $keyName : 'foreign_' . $fromColumn . '_' . $onTable . '_' . $toColumn);

        $this->keys[] = new ForeignKey($keyName, $fromColumn, $toColumn, $onTable);
    }

    public function index($columns)
    {
        $this->keys[] = new Index($columns);
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