<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\Resolver\NullColumnDefinitionResolver;

class NullQueryBuilder extends QueryBuilder {

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    protected function getColumnDefinitionResolver()
    {
        return new NullColumnDefinitionResolver();
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

    protected function buildIndexDefinition(Index $index) 
    {
        return null;
    }

    protected function buildForeignKeyDefinition(ForeignKey $foreignKey)
    {
        return null;
    }

    protected function buildPrimaryKeyDefinition(PrimaryKey $primaryKey)
    {
        return null;
    }

    protected function buildUniqueKeyDefinition(UniqueKey $uniqueKey)
    {
        return null;
    }

}