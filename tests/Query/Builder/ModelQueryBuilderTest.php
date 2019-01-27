<?php

namespace Tests\Query\Builder;

use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Query\Builder\ModelQueryBuilder;
use Intersect\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;
use Tests\Model\TestModel;
use Tests\Model\TestRelationOneModel;
use Tests\Model\TestRelationTwoModel;

class ModelQueryBuilderTest extends TestCase {
    
    /** @var TestModel $testModel */
    private $testModel;

    /** @var TestRelationOneModel $testRelationOneModel */
    private $testRelationOneModel;

    /** @var TestRelationTwoModel $testRelationTwoModel */
    private $testRelationTwoModel;

    private $testTableAlias;
    private $testRelationalTableOneAlias;
    private $testRelationalTableTwoAlias;

    protected function setUp()
    {
        parent::setUp();

        $this->testModel = new TestModel();
        $this->testRelationOneModel = new TestRelationOneModel();
        $this->testRelationTwoModel = new TestRelationTwoModel();

        $this->testTableAlias = AliasFactory::getAlias($this->testModel->getTableName());
        $this->testRelationalTableOneAlias = AliasFactory::getAlias($this->testRelationOneModel->getTableName());
        $this->testRelationalTableTwoAlias = AliasFactory::getAlias('data');
    }

    public function test_buildSelectQuery_allColumns()
    {
        $modelQueryBuilder = new ModelQueryBuilder($this->testModel);
        
        $query = $modelQueryBuilder->buildSelectQuery();

        $alias = $this->testTableAlias;

        $this->assertEquals("select " . $alias . ".* from `tests` as " . $alias, strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_withColumns()
    {
        $modelQueryBuilder = new ModelQueryBuilder($this->testModel);

        $query = $modelQueryBuilder->buildSelectQuery(['unit', 'test']);

        $alias = $this->testTableAlias;

        $this->assertEquals("select " . $alias . ".unit as '" . $alias . ".unit', " . $alias . ".test as '" . $alias . ".test' from `tests` as " . $alias . "", strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_withOrderAndLimit()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->setOrder('test desc');
        $queryParameters->setLimit(5);

        $modelQueryBuilder = new ModelQueryBuilder($this->testModel, $queryParameters);

        $query = $modelQueryBuilder->buildSelectQuery(['unit']);

        $alias = $this->testTableAlias;

        $this->assertEquals("select " . $alias . ".unit as '" . $alias . ".unit' from `tests` as " . $alias . " order by test desc limit 5", strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_withWhere()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->equals('test', 'unit');

        $modelQueryBuilder = new ModelQueryBuilder($this->testModel, $queryParameters);

        $query = $modelQueryBuilder->buildSelectQuery(['unit']);

        $alias = $this->testTableAlias;

        $this->assertEquals("select " . $alias . ".unit as '" . $alias . ".unit' from `tests` as " . $alias . " where " . $alias . ".test = :" . $alias . "_test", strtolower($query->getSql()));
    }

    public function test_buildSelectQuery_allColumns_withEagerRelationship()
    {
        $modelQueryBuilder = new ModelQueryBuilder($this->testRelationOneModel);

        $query = $modelQueryBuilder->buildSelectQuery();

        $aliasOne = $this->testRelationalTableOneAlias;
        $aliasTwo = $this->testRelationalTableTwoAlias;

        $this->assertEquals("select " . $aliasOne . ".*, " . $aliasTwo . ".id as '" . $aliasTwo . ".id', " . $aliasTwo . ".title as '" . $aliasTwo . ".title' from `test_relation_one` as " . $aliasOne . " left join test_relation_two as " . $aliasTwo . " on " . $aliasOne . ".data_id = " . $aliasTwo . ".id", strtolower($query->getSql()));
    }

}