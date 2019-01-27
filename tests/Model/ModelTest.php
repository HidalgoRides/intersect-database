<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {

    public function test_findById_withEagerRelationship()
    {
        $testRelationOneModel = TestRelationOneModel::findOne();

        $this->assertNotNull($testRelationOneModel->data);
        $this->assertEquals('data title', $testRelationOneModel->data->title);
    }

}