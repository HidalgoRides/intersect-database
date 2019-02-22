<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Model\ModelHelper;
use Tests\Stubs\User;

class ModelHelperTest extends TestCase {

    public function test_normalize_withoutConvertAttributeKeys()
    {
        $models = User::with(['name']);
        $model = $models[0];
        $model->addMetaData('unit', 'test');
        
        $normalizedModel = ModelHelper::normalize($model);

        $this->assertNormalizedModelWithoutAttributeKeyConversion($normalizedModel);
    }

    public function test_normalize_withConvertAttributeKeys()
    {
        $models = User::with(['name']);
        $model = $models[0];
        $model->addMetaData('unit', 'test');
        
        $normalizedModel = ModelHelper::normalize($model, true);

        $this->assertNormalizedModelWithAttributeKeyConversion($normalizedModel);
    }

    public function test_normalizeList_withoutConvertAttributeKeys()
    {
        $models = User::with(['name']);
        $model = $models[0];
        $model->addMetaData('unit', 'test');

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
        $models = User::with(['name']);
        $model = $models[0];
        $model->addMetaData('unit', 'test');

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

    private function assertNormalizedModelWithoutAttributeKeyConversion($normalizedModel)
    {
        $this->assertArrayHasKey('id', $normalizedModel);
        $this->assertArrayHasKey('email', $normalizedModel);
        $this->assertArrayHasKey('name_id', $normalizedModel);
        $this->assertArrayHasKey('phone_id', $normalizedModel);

        $this->assertArrayHasKey('name', $normalizedModel);
        
        $normalizedName = $normalizedModel['name'];
        $this->assertIsArray($normalizedName);
        $this->assertArrayHasKey('id', $normalizedName);
        $this->assertArrayHasKey('first_name', $normalizedName);
        $this->assertArrayHasKey('last_name', $normalizedName);

        $this->assertArrayHasKey('meta_data', $normalizedModel);

        $normalizedMetaData = $normalizedModel['meta_data'];
        $this->assertArrayHasKey('unit', $normalizedMetaData);
    }

    private function assertNormalizedModelWithAttributeKeyConversion($normalizedModel)
    {
        $this->assertArrayHasKey('id', $normalizedModel);
        $this->assertArrayHasKey('email', $normalizedModel);
        $this->assertArrayHasKey('nameId', $normalizedModel);
        $this->assertArrayHasKey('phoneId', $normalizedModel);

        $this->assertArrayHasKey('name', $normalizedModel);
        
        $normalizedName = $normalizedModel['name'];
        $this->assertIsArray($normalizedName);
        $this->assertArrayHasKey('id', $normalizedName);
        $this->assertArrayHasKey('firstName', $normalizedName);
        $this->assertArrayHasKey('lastName', $normalizedName);

        $this->assertArrayHasKey('metaData', $normalizedModel);

        $normalizedMetaData = $normalizedModel['metaData'];
        $this->assertArrayHasKey('unit', $normalizedMetaData);
    }

}