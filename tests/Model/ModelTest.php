<?php

namespace Tests\Model;

use Tests\Stubs\Name;
use Tests\Stubs\User;
use Tests\Stubs\Phone;
use Tests\Stubs\Address;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\PartialAssociation;
use Tests\Stubs\UserNameAssociation;
use Intersect\Database\Query\QueryParameters;

class ModelTest extends TestCase {

    public function test_count()
    {
        $count = Address::count();
        $this->assertEquals(2, $count);
    }

    public function test_count_withQueryParameters()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->equals('zip_code', '12345');

        $count = Address::count($queryParameters);
        $this->assertEquals(1, $count);
    }

    public function test_getMaxValue()
    {
        $maxValue = Address::getMaxValue('id');
        $this->assertEquals(2, $maxValue);
    }

    public function test_getMaxValue_withQueryParameters()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->equals('zip_code', '12345');

        $maxValue = Address::getMaxValue('id', $queryParameters);
        $this->assertEquals(1, $maxValue);
    }

    public function test_find()
    {
        $users = User::find();
        $this->assertNotNull($users);
        $this->assertTrue(count($users) > 0);
    }

    public function test_find_withExplicitColumns()
    {
        $queryParameters = new QueryParameters();
        $queryParameters->setColumns(['email']);
        $queryParameters->setLimit(1);
         $users = User::find($queryParameters);
        $this->assertNotNull($users);
        $this->assertTrue(count($users) == 1);
        $user = $users[0];
        $this->assertNull($user->id);
        $this->assertNotNull($user->email);
    }

    public function test_find_withGroupParameters()
    {
        $uniqueId = uniqid();

        $user = new User();
        $user->email = 'unit-test-' . $uniqueId . '-1@test.com';
        $user->save();
        $user = new User();
        $user->email = 'unit-test-' . $uniqueId . '-2@test.com';
        $user->save();

        $queryParameters = new QueryParameters();
        $queryParameters->groupOr(function(QueryParameters $queryParameters) use ($uniqueId) {
            $queryParameters->equals('email', 'unit-test-' . $uniqueId . '-1@test.com');
            $queryParameters->equals('email', 'unit-test-' . $uniqueId . '-2@test.com');
        });

        $users = User::find($queryParameters);
        $this->assertCount(2, $users);
    }

    public function test_find_withNestedGroupParameters()
    {
        $uniqueId = uniqid();

        $user = new User();
        $user->email = 'unit-test-' . $uniqueId . '-1@test.com';
        $user->password = '123';
        $user->status = 1;
        $user->save();
        $user = new User();
        $user->email = 'unit-test-' . $uniqueId . '-2@test.com';
        $user->password = 'password';
        $user->status = 1;
        $user->save();

        $queryParameters = new QueryParameters();
        $queryParameters->setColumns(['id']);
        $queryParameters->groupOr(function(QueryParameters $queryParameters) use ($uniqueId) {
            $queryParameters->group(function(QueryParameters $queryParameters) use ($uniqueId) {
                $queryParameters->equals('email', 'unit-test-' . $uniqueId . '-1@test.com');
                $queryParameters->equals('password', '123');
            });
            $queryParameters->group(function(QueryParameters $queryParameters) use ($uniqueId) {
                $queryParameters->equals('email', 'unit-test-' . $uniqueId . '-2@test.com');
                $queryParameters->equals('password', 'password');
            });
        });
        $queryParameters->equals('status', 1);

        $users = User::find($queryParameters);
        $this->assertCount(2, $users);
    }

    public function test_findById()
    {
        $user = User::findById(1);
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
    }

    public function test_findAssociationsForColumnTwo()
    {
        $user = User::findOne();
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
    }

    public function test_delete()
    {
        $user = new User();
        $user->email = 'unit-test-' . uniqid() . '@test.com';

        $newUser = $user->save();
        $this->assertNotNull($newUser);
        $this->assertNotNull($newUser->getPrimaryKeyValue());

        $newUser->delete();

        $this->assertNull(User::findById($newUser->id));
    }

    public function test_save()
    {
        $user = new User();
        $user->email = 'unit-test-' . uniqid() . '@test.com';

        $newUser = $user->save();
        $this->assertNotNull($newUser);
        $this->assertNotNull($newUser->getPrimaryKeyValue());
    }

    public function test_update()
    {
        $email = 'unit-test-' . uniqid() . '@test.com';
        $newEmail = 'unit-test-' . uniqid() . '@test.com';

        $user = new User();
        $user->email = $email;

        $newUser = $user->save();
        $this->assertNotNull($newUser);
        $this->assertNotNull($newUser->getPrimaryKeyValue());

        $newUser->email = $newEmail;

        $updatedUser = $newUser->save();
        $this->assertNotNull($updatedUser);
        $this->assertNotNull($updatedUser->getPrimaryKeyValue());
        $this->assertEquals($newUser->id, $updatedUser->id);
        $this->assertNotEquals($email, $updatedUser->email);
    }

    public function test_metaData() 
    {
        $user = new User();
        $user->email = 'unit-test-' . uniqid() . '@test.com';
        $user->setMetaData([
            'unit' => 'test'
        ]);

        $newUser = $user->save();
        $this->assertNotNull($newUser->getPrimaryKeyValue());

        $metaData = $newUser->getMetaDataByKey('unit');
        $this->assertNotNull($metaData);
        $this->assertEquals('test', $metaData);

        $metaData = $newUser->addMetaData('new', 'data');

        $this->assertNotNull($newUser->meta_data);

        $newUser = $newUser->save();
        $metaDataUnit = $newUser->getMetaDataByKey('unit');
        $metaDataNew = $newUser->getMetaDataByKey('new');
        $this->assertNotNull($metaDataUnit);
        $this->assertNotNull($metaDataNew);
        $this->assertEquals('test', $metaDataUnit);
        $this->assertEquals('data', $metaDataNew);

        $newUser->clearAllMetaData();
        $newUser = $newUser->save();

        $this->assertNull($newUser->getMetaData());
    }

    public function test_dirtyUpdates()
    {
        $user = new User();
        $user->email = 'unit-test-' . uniqid() . '@test.com';
        $user->save();

        $this->assertNotNull($user->id);
        $this->assertNotNull($user->date_created);
        $this->assertNull($user->date_updated);

        $user->save();
        $this->assertNull($user->date_updated);

        $user->email = 'unit-test-' . uniqid() . '@test.com';
        
        $user->save();
        $this->assertNotNull($user->date_updated);
    }

    public function test_isDirty_rootChanged()
    {
        $user = User::findOne();

        $this->assertFalse($user->isDirty());

        $user->email = 'test';
        $this->assertTrue($user->isDirty());
    }

    public function test_isDirty_setAttributeInvoked()
    {
        $user = User::findOne();

        $this->assertFalse($user->isDirty());

        $user->setAttribute('email', 'test');
        
        $this->assertTrue($user->isDirty());
        $this->assertEquals('test', $user->email);
    }

    public function test_isDirty_relationshipChanged()
    {
        $user = User::findOne();
        $phone = $user->phone;

        $this->assertFalse($user->isDirty());

        $phone->number = 'test';
        $this->assertTrue($user->isDirty());
    }

    public function test_multiplePrimaryKeyInDBConstraintError()
    {
        $partialAssociation = new PartialAssociation();
        $partialAssociation->key_one = 1;
        $partialAssociation->key_two = 5;

        $pa = $partialAssociation->save();

        $queryParameters = new QueryParameters();
        $queryParameters->equals('key_one', 1);
        $queryParameters->equals('key_two', 5);

        $this->assertNotNull(PartialAssociation::find($queryParameters));
    }

    public function test_bulkCreate()
    {
        $models = Phone::bulkCreate([
            ['number' => '123'],
            ['number' => '456'],
            ['number' => '789']
        ]);
        
        $this->assertCount(3, $models);

        foreach ($models as $model)
        {
            $this->assertNotNull($model->id);
        }
    }

    public function test_bulkCreate_withPrimaryKeyValueSet()
    {
        Phone::bulkCreate([
            [
                'id' => 999,
                'number' => '999'
            ]
        ]);

        $q = new QueryParameters();
        $q->equals('id', 999);
        
        $model = Phone::findOne($q);

        $this->assertNotNull($model);
        $this->assertEquals(999, $model->id);
        $this->assertEquals('999', $model->number);
    }

    public function test_truncate() 
    {
        $phoneData = [];

        for ($i = 0; $i <= 10; $i++)
        {
            $phoneData[] = ['number' => '12345678' . $i];
        }

        Phone::bulkCreate($phoneData);

        $this->assertTrue(Phone::count() >= 10);

        Phone::truncate();

        $this->assertEquals(0, Phone::count());
    }

    public function test_withTransaction_success() 
    {
        Phone::truncate();
        $this->assertEquals(0, Phone::count());

        Phone::bulkCreate([
            ['number' => 123]
        ]);

        $this->assertEquals(1, Phone::count());

        Phone::withTransaction(function() {
            Phone::bulkCreate([
                ['number' => 456]
            ]);
        });

        $this->assertEquals(2, Phone::count());
    }

    public function test_withTransaction_rollback_noFallback() 
    {
        Phone::truncate();
        $this->assertEquals(0, Phone::count());

        Phone::bulkCreate([
            ['number' => 123]
        ]);

        $this->assertEquals(1, Phone::count());

        Phone::withTransaction(function() {
            Phone::bulkCreate([
                ['number' => 456],
                ['number' => 456, 'unknown column' => 'should cause a rollback']
            ]);
        });

        $this->assertEquals(1, Phone::count());
    }

    public function test_withTransaction_rollback_withFallback() 
    {
        Phone::truncate();

        $fallbackCalled = false;

        Phone::withTransaction(function() {
            Phone::bulkCreate([
                ['number' => 456],
                ['number' => 456, 'unknown column' => 'should cause a rollback']
            ]);
        }, function() use (&$fallbackCalled) {
            $fallbackCalled = true;
        });

        $this->assertEquals(0, Phone::count());
        $this->assertTrue($fallbackCalled);
    }

    public function test_withPagination() 
    {
        Phone::truncate();
        Phone::bulkCreate([
            ['number' => 123],
            ['number' => 456]
        ]);

        $this->assertEquals(2, Phone::count());

        $params = new QueryParameters();
        $params->limit(1);
        $params->start(0);
        
        $result = Phone::findOne($params);
        $this->assertEquals(123, $result->number);

        $params->start(1);

        $result = Phone::findOne($params);
        $this->assertEquals(456, $result->number);
    }

    public function test_greaterThan() 
    {
        Phone::truncate();
        Phone::bulkCreate([
            ['id' => 1, 'number' => 123],
            ['id' => 2, 'number' => 456],
            ['id' => 3, 'number' => 789]
        ]);

        $this->assertEquals(3, Phone::count());

        $params = new QueryParameters();
        $params->greaterThan('id', 2);
        
        $results = Phone::find($params);
        $this->assertEquals(1, count($results));
        $this->assertEquals(789, $results[0]->number);
    }

    public function test_greaterThanOrEqual() 
    {
        Phone::truncate();
        Phone::bulkCreate([
            ['id' => 1, 'number' => 123],
            ['id' => 2, 'number' => 456],
            ['id' => 3, 'number' => 789]
        ]);

        $this->assertEquals(3, Phone::count());

        $params = new QueryParameters();
        $params->greaterThanOrEqual('id', 2);
        
        $results = Phone::find($params);
        $this->assertEquals(2, count($results));
        $this->assertEquals(456, $results[0]->number);
        $this->assertEquals(789, $results[1]->number);
    }

    public function test_lessThan() 
    {
        Phone::truncate();
        Phone::bulkCreate([
            ['id' => 1, 'number' => 123],
            ['id' => 2, 'number' => 456],
            ['id' => 3, 'number' => 789]
        ]);

        $this->assertEquals(3, Phone::count());

        $params = new QueryParameters();
        $params->lessThan('id', 2);
        
        $results = Phone::find($params);
        $this->assertEquals(1, count($results));
        $this->assertEquals(123, $results[0]->number);
    }

    public function test_lessThanOrEqual() 
    {
        Phone::truncate();
        Phone::bulkCreate([
            ['id' => 1, 'number' => 123],
            ['id' => 2, 'number' => 456],
            ['id' => 3, 'number' => 789]
        ]);

        $this->assertEquals(3, Phone::count());

        $params = new QueryParameters();
        $params->lessThanOrEqual('id', 2);
        
        $results = Phone::find($params);
        $this->assertEquals(2, count($results));
        $this->assertEquals(123, $results[0]->number);
        $this->assertEquals(456, $results[1]->number);
    }

    public function test_hasAssociation()
    {
        $user = User::findOne();
        $name = Name::findOne();

        UserNameAssociation::truncate();
        UserNameAssociation::bulkCreate([
            ['user_id' => $user->id, 'name_id' => $name->id]
        ]);

        $nameFromAssociation = $user->name_association;

        $this->assertNotNull($nameFromAssociation);
        $this->assertEquals($name->id, $nameFromAssociation->id);
    }

    public function test_hasAssociations()
    {
        $user = User::findOne();
        
        UserNameAssociation::truncate();
        $names = Name::bulkCreate([
            ['first_name' => 'Test', 'last_name' => 'LastName1'],
            ['first_name' => 'Test', 'last_name' => 'LastName2'],
        ]);

        UserNameAssociation::bulkCreate([
            ['user_id' => $user->id, 'name_id' => $names[0]->id],
            ['user_id' => $user->id, 'name_id' => $names[1]->id]
        ]);

        $namesFromAssociations = $user->name_associations;

        $this->assertNotNull($namesFromAssociations);
        $this->assertCount(2, $namesFromAssociations);
        $this->assertEquals('LastName1', $namesFromAssociations[0]->last_name);
        $this->assertEquals('LastName2', $namesFromAssociations[1]->last_name);
    }

}