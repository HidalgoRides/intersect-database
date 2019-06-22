<?php

namespace Intersect\Database\Schema\Constraint;

class ForeignKey {

    private $fromColumn;
    private $toColumn;
    private $onTable;
    private $tableSchema;

    public function __construct($fromColumn, $toColumn, $onTable, $tableSchema = 'public')
    {
        $this->fromColumn = $fromColumn;
        $this->toColumn = $toColumn;
        $this->onTable = $onTable;
        $this->tableSchema = $tableSchema;
    }

    public function getFromColumn()
    {
        return $this->fromColumn;
    }

    public function getToColumn()
    {
        return $this->toColumn;
    }

    public function getOnTable()
    {
        return $this->onTable;
    }

    public function getTableSchema()
    {
        return $this->tableSchema;
    }

}