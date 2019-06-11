<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Connection\Connection;

class NullQueryBuilder extends QueryBuilder {

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    protected function getColumnDefinitionResolver()
    {
        return NullColumnDefinitionResolver();
    }

    protected function buildCountQuery()
    {
        return null;
    }

    protected function buildSelectQuery()
    {
        return null;
    }

    protected function buildDeleteQuery()
    {
        return null;
    }

    protected function buildUpdateQuery()
    {
        return null;
    }

    protected function buildInsertQuery()
    {
        return null;
    }

    protected function buildColumnQuery()
    {
        return null;
    }

}