<?php

namespace Tests\Model;

use Tests\Stubs\User;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {

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

        $newUser->delete();

        $this->assertNull(User::findById($newUser->id));
    }

    public function test_save()
    {
        $user = new User();
        $user->email = 'unit-test-' . uniqid() . '@test.com';

        $newUser = $user->save();
        $this->assertNotNull($newUser);
    }

    public function test_update()
    {
        $email = 'unit-test-' . uniqid() . '@test.com';
        $newEmail = 'unit-test-' . uniqid() . '@test.com';

        $user = new User();
        $user->email = $email;

        $newUser = $user->save();
        $this->assertNotNull($newUser);

        $newUser->email = $newEmail;

        $updatedUser = $newUser->save();
        $this->assertNotNull($updatedUser);
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

}