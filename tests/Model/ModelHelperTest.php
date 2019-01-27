<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;
use Tests\Model\TestModel;
use Intersect\Database\Model\ModelHelper;

class ModelHelperTest extends TestCase {

    public function test_normalize_withoutConvertAttributeKeys()
    {
        $testModel = TestModel::findOne();
        
        $normalizedModel = ModelHelper::normalize($testModel);

        $this->assertArrayHasKey('id', $normalizedModel);
        $this->assertArrayHasKey('data', $normalizedModel);
        $this->assertArrayHasKey('foo_bar', $normalizedModel);
    }

    public function test_normalize_withConvertAttributeKeys()
    {
        $testModel = TestModel::findOne();
        
        $normalizedModel = ModelHelper::normalize($testModel, true);

        $this->assertArrayHasKey('id', $normalizedModel);
        $this->assertArrayHasKey('data', $normalizedModel);
        $this->assertArrayHasKey('fooBar', $normalizedModel);
    }

    public function test_normalizeList_withoutConvertAttributeKeys()
    {
        $testModel = TestModel::findOne();

        $models = [
            $testModel,
            $testModel
        ];
        
        $normalizedModels = ModelHelper::normalizeList($models);

        foreach ($normalizedModels as $normalizedModel)
        {
            $this->assertArrayHasKey('id', $normalizedModel);
            $this->assertArrayHasKey('data', $normalizedModel);
            $this->assertArrayHasKey('foo_bar', $normalizedModel);
        }
    }

    public function test_normalizeList_withConvertAttributeKeys()
    {
        $testModel = TestModel::findOne();

        $models = [
            $testModel,
            $testModel
        ];
        
        $normalizedModels = ModelHelper::normalizeList($models, true);

        foreach ($normalizedModels as $normalizedModel)
        {
            $this->assertArrayHasKey('id', $normalizedModel);
            $this->assertArrayHasKey('data', $normalizedModel);
            $this->assertArrayHasKey('fooBar', $normalizedModel);
        }
    }

}