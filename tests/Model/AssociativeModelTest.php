<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;
use Tests\Stubs\Association;

class AssociativeModelTest extends TestCase {

    public function test_findAssociation()
    {
        $association = Association::findAssociation(1, 1);
        $this->assertNotNull($association);
    }

    public function test_findAssociationsForColumnOne()
    {
        $associations = Association::findAssociationsForColumnOne(1);
        $this->assertNotNull($associations);
        $this->assertCount(2, $associations);
    }

    public function test_findAssociationsForColumnTwo()
    {
        $associations = Association::findAssociationsForColumnTwo(1);
        $this->assertNotNull($associations);
        $this->assertCount(2, $associations);
    }

    public function test_delete()
    {
        $association = new Association();
        $association->key_one = 3;
        $association->key_two = 3;

        $newAssociation = $association->save();
        $this->assertNotNull($newAssociation);

        $newAssociation->delete();

        $this->assertNull(Association::findAssociation(3, 3));
    }

    public function test_save()
    {
        $association = new Association();
        $association->key_one = 4;
        $association->key_two = 4;

        $newAssociation = $association->save();
        $this->assertNotNull($newAssociation);
    }

    public function test_save_duplicateKeyGetsUpdated()
    {
        $association = new Association();
        $association->key_one = 5;
        $association->key_two = 5;
        $association->data = 'unit';

        $newAssociation = $association->save();
        $this->assertNotNull($newAssociation);
        $this->assertEquals('unit', $newAssociation->data);

        $association->data = 'test';

        $newAssociation = $association->save();
        $this->assertNotNull($newAssociation);
        $this->assertEquals('test', $newAssociation->data);
    }

}