<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnDefinition;
use Intersect\Database\Schema\Constraint\ForeignKey;

class Blueprint {

    /** @var ColumnDefinition[] */
    private $columnDefinitions = [];
    private $uniqueKeys = [];
    private $primaryKeys = [];
    /** @var ForeignKey[] */
    private $foreignKeys = [];

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

    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }

    public function unique($names, $keyName = null)
    {
        if (!is_array($names))
        {
            $names = [$names];
        }

        $keyName = (!is_null($keyName) ? $keyName : 'unique_' . $this->tableName . '_' . implode('_', $names));

        $this->uniqueKeys[$keyName] = $names;
    }

    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    public function primary($names, $keyName = null)
    {
        if (!is_array($names))
        {
            $names = [$names];
        }

        $keyName = (!is_null($keyName) ? $keyName : 'primary_' . $this->tableName . '_' . implode('_', $names));

        $this->primaryKeys[$keyName] = $names;
    }

    /** @return ForeignKey[] */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    public function foreign($fromColumn, $toColumn, $onTable, $keyName = null)
    {
        $keyName = (!is_null($keyName) ? $keyName : 'foreign_' . $fromColumn . '_' . $onTable . '_' . $toColumn);

        $this->foreignKeys[$keyName] = new ForeignKey($fromColumn, $toColumn, $onTable);
    }

    private function addColumnDefinition(ColumnDefinition $columnDefinition)
    {
        $this->columnDefinitions[] = $columnDefinition;
    }

}