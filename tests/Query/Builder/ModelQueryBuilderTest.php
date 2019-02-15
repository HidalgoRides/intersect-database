<?php

namespace Tests\Query\Builder;

use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Query\Builder\ModelQueryBuilder;
use Intersect\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\Name;
use Tests\Stubs\User;

class ModelQueryBuilderTest extends TestCase {

    public function test_buildSelectQuery_allColumns()
    {
        $model = new Name();
        $modelQueryBuilder = new ModelQueryBuilder($model);
        
        $query = $modelQueryBuilder->buildSelectQuery();
        $alias = AliasFactory::getAlias($model->getTableName());

        $this->assertEquals("select " . $alias . ".* from `names` as " . $alias, strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_withColumns()
    {
        $model = new Name();
        $modelQueryBuilder = new ModelQueryBuilder($model);

        $query = $modelQueryBuilder->buildSelectQuery(['unit', 'test']);
        $alias = AliasFactory::getAlias($model->getTableName());

        $this->assertEquals("select " . $alias . ".unit as '" . $alias . ".unit', " . $alias . ".test as '" . $alias . ".test' from `names` as " . $alias . "", strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_withOrderAndLimit()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->setOrder('test desc');
        $queryParameters->setLimit(5);

        $model = new Name();
        $modelQueryBuilder = new ModelQueryBuilder($model, $queryParameters);

        $query = $modelQueryBuilder->buildSelectQuery(['unit']);
        $alias = AliasFactory::getAlias($model->getTableName());

        $this->assertEquals("select " . $alias . ".unit as '" . $alias . ".unit' from `names` as " . $alias . " order by test desc limit 5", strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_withWhere()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->equals('test', 'unit');

        $model = new Name();
        $modelQueryBuilder = new ModelQueryBuilder($model, $queryParameters);

        $query = $modelQueryBuilder->buildSelectQuery(['unit']);
        $alias = AliasFactory::getAlias($model->getTableName());

        $this->assertEquals("select " . $alias . ".unit as '" . $alias . ".unit' from `names` as " . $alias . " where " . $alias . ".test = :" . $alias . "_test", strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_allColumns_withEagerRelationship()
    {
        $model = new User();
        $modelQueryBuilder = new ModelQueryBuilder($model);

        $query = $modelQueryBuilder->buildSelectQuery();

        $aliasOne = AliasFactory::getAlias($model->getTableName());
        $aliasTwo = AliasFactory::getAlias('phone');

        $this->assertEquals("select " . $aliasOne . ".*, " . $aliasTwo . ".id as '" . $aliasTwo . ".id', " . $aliasTwo . ".number as '" . $aliasTwo . ".number' from `users` as " . $aliasOne . " left join phones as " . $aliasTwo . " on " . $aliasOne . ".phone_id = " . $aliasTwo . ".id", strtolower($query->getSql()));
    }

}