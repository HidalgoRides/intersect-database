<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Model\ModelHelper;
use Tests\Stubs\User;

class ModelHelperTest extends TestCase {

    public function test_normalize_withoutConvertAttributeKeys()
    {
        $model = User::findOne();
        
        $normalizedModel = ModelHelper::normalize($model);

        $this->assertNormalizedModelWithoutAttributeKeyConversion($normalizedModel);
    }

    public function test_normalize_withConvertAttributeKeys()
    {
        $model = User::findOne();
        
        $normalizedModel = ModelHelper::normalize($model, true);

        $this->assertNormalizedModelWithAttributeKeyConversion($normalizedModel);
    }

    public function test_normalizeList_withoutConvertAttributeKeys()
    {
        $model = User::findOne();

        $models = [
            $model,
            $model
        ];
        
        $normalizedModels = ModelHelper::normalizeList($models);

        foreach ($normalizedModels as $normalizedModel)
        {
            $this->assertNormalizedModelWithoutAttributeKeyConversion($normalizedModel);
        }
    }

    public function test_normalizeList_withConvertAttributeKeys()
    {
        $model = User::findOne();

        $models = [
            $model,
            $model
        ];
        
        $normalizedModels = ModelHelper::normalizeList($models, true);

        foreach ($normalizedModels as $normalizedModel)
        {
            $this->assertNormalizedModelWithAttributeKeyConversion($normalizedModel);
        }
    }

    private function assertNormalizedModelWithoutAttributeKeyConversion($model)
    {
        $this->assertArrayHasKey('id', $model);
        $this->assertArrayHasKey('email', $model);
        $this->assertArrayHasKey('name_id', $model);
        $this->assertArrayHasKey('phone_id', $model);
        $this->assertArrayHasKey('phone', $model);
    }

    private function assertNormalizedModelWithAttributeKeyConversion($model)
    {
        $this->assertArrayHasKey('id', $model);
        $this->assertArrayHasKey('email', $model);
        $this->assertArrayHasKey('nameId', $model);
        $this->assertArrayHasKey('phoneId', $model);
        $this->assertArrayHasKey('phone', $model);
    }

}