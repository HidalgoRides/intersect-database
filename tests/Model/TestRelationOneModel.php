<?php

namespace Tests\Model;

use Intersect\Database\Model\Model;
use Intersect\Database\Model\Relationship\EagerRelationship;
use Intersect\Database\Model\Relationship\Relational;

class TestRelationOneModel extends Model implements Relational {
    protected $tableName = 'test_relation_one';

    public function getEagerRelationshipMap()
    {
        return [
            new EagerRelationship(TestRelationTwoModel::class, 'data_id', 'data')
        ];
    }

    public function getLazyRelationshipMap()
    {
        return [];
    }

}