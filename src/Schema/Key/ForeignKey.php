<?php

namespace Intersect\Database\Schema\Key;

class ForeignKey extends Key {

    protected $prefix = 'fidx_';

    private $fromColumn;
    private $toColumn;
    private $onTable;
    private $tableSchema;

    public function __construct($sourceTable, $fromColumn, $toColumn, $onTable, $keyName = null, $tableSchema = 'public')
    {
        $this->fromColumn = $fromColumn;
        $this->toColumn = $toColumn;
        $this->onTable = $onTable;
        $this->tableSchema = $tableSchema;

        if (is_null($keyName))
        {
            $keyName = $this->generateName($sourceTable);
        }

        parent::__construct($sourceTable, [$fromColumn], $keyName);
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

    private function generateName($sourceTableName)
    {
        return $this->prefix . $sourceTableName . '_' . $this->fromColumn . '_' . $this->onTable . '_' . $this->toColumn;
    }

}