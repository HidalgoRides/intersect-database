<?php

namespace Intersect\Database\Schema\Key;

class ForeignKey extends Key {

    protected $prefix = 'foreign_';

    private $fromColumn;
    private $toColumn;
    private $onTable;
    private $tableSchema;

    public function __construct($keyName, $fromColumn, $toColumn, $onTable, $tableSchema = 'public')
    {
        parent::__construct(null, [$fromColumn], $this->prefix . $keyName);

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