<?php

namespace Intersect\Database\Schema;

use Intersect\Database\Schema\ColumnType;
use Intersect\Database\Schema\ColumnDefinition;

class ColumnBlueprint {

    /** @var ColumnDefinition */
    private $columnDefinition;

    public function getColumnDefinition()
    {
        return $this->columnDefinition;
    }

    public function datetime($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::DATETIME);
        return $this->columnDefinition;
    }

    public function numeric($name, $precision, $scale)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::NUMERIC);
        $this->columnDefinition->precision($precision);
        $this->columnDefinition->scale($scale);

        return $this->columnDefinition;
    }

    public function increments($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::INTEGER);
        $this->columnDefinition->primary();
        $this->columnDefinition->autoIncrement();

        return $this->columnDefinition;
    }

    public function tinyInteger($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::TINY_INT);
        return $this->columnDefinition;
    }

    public function smallInteger($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::SMALL_INT);
        return $this->columnDefinition;
    }

    public function mediumInteger($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::MEDIUM_INT);
        return $this->columnDefinition;
    }

    public function integer($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::INTEGER);
        return $this->columnDefinition;
    }

    public function bigInteger($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::BIG_INT);
        return $this;
    }

    public function string($name, $length = 255)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::STRING);
        $this->columnDefinition->length($length);

        return $this->columnDefinition;
    }

    public function json($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::JSON);
        return $this->columnDefinition;
    }

    public function mediumText($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::MEDIUM_TEXT);
        return $this->columnDefinition;
    }

    public function longText($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::LONG_TEXT);
        return $this->columnDefinition;
    }

    public function text($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::TEXT);
        return $this->columnDefinition;
    }

    public function timestamp($name)
    {
        $this->columnDefinition = new ColumnDefinition($name, ColumnType::TIMESTAMP);
        return $this->columnDefinition;
    }

}