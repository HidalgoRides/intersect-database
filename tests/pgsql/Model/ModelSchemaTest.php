<?php

namespace Tests\Model;

use PHPUnit\Framework\TestCase;
use Tests\Stubs\Unit;

class ModelSchemaTest extends TestCase {

    public function test_modelWithSchemaDefined()
    {
        $units = Unit::find();
        $this->assertCount(1, $units);
    }

}