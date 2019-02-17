<?php

namespace Intersect\Database\Query\Builder\Condition;

class QueryCondition {

    private $column;
    private $operand;
    private $value;

    public function __construct($column, $operand, $value = null)
    {
        $this->column = $column;
        $this->operand = $operand;
        $this->value = $value;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getOperand()
    {
        return $this->operand;
    }

    public function getValue()
    {
        return $this->value;
    }

}