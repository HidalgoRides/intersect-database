<?php

namespace Tests\Model;

use Tests\Stubs\User;
use Tests\Stubs\Address;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\PartialAssociation;
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

}