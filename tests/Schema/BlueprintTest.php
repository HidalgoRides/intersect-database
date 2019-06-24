<?php

namespace Tests\Schema;

use PHPUnit\Framework\TestCase;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\ForeignKey;

class BlueprintTest extends TestCase {

    /** @var Blueprint */
    private $blueprint;

    protected function setUp()
    {
        $this->blueprint = new Blueprint('test');
        $this->assertEquals('test', $this->blueprint->getTableName());
    }

    public function test_datetime()
    {
        $definition = $this->blueprint->datetime('date_added');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('date_added', $definition->getName());
        $this->assertEquals('datetime', $definition->getType());
    }

    public function test_numeric()
    {
        $definition = $this->blueprint->numeric('price', 4, 2);

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('price', $definition->getName());
        $this->assertEquals('numeric', $definition->getType());
        $this->assertEquals(4, $definition->getPrecision());
        $this->assertEquals(2, $definition->getScale());
    }

    public function test_increments()
    {
        $definition = $this->blueprint->increments('id');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());
        $this->assertCount(1, $this->blueprint->getKeys());
        $this->assertTrue($this->blueprint->getKeys()[0] instanceof PrimaryKey);

        $this->assertNotNull($definition);
        $this->assertEquals('id', $definition->getName());
        $this->assertEquals('integer', $definition->getType());
        $this->assertTrue($definition->isPrimary());
        $this->assertTrue($definition->isAutoIncrement());
    }

    public function test_tinyInteger()
    {
        $definition = $this->blueprint->tinyInteger('age');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('age', $definition->getName());
        $this->assertEquals('tinyint', $definition->getType());
    }

    public function test_smallInteger()
    {
        $definition = $this->blueprint->smallInteger('age');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('age', $definition->getName());
        $this->assertEquals('smallint', $definition->getType());
    }

    public function test_mediumInteger()
    {
        $definition = $this->blueprint->mediumInteger('age');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('age', $definition->getName());
        $this->assertEquals('mediumint', $definition->getType());
    }

    public function test_bigInteger()
    {
        $definition = $this->blueprint->bigInteger('age');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('age', $definition->getName());
        $this->assertEquals('bigint', $definition->getType());
    }

    public function test_integer()
    {
        $definition = $this->blueprint->integer('age');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('age', $definition->getName());
        $this->assertEquals('integer', $definition->getType());
    }

    public function test_string_defaultLength()
    {
        $definition = $this->blueprint->string('email');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('email', $definition->getName());
        $this->assertEquals('string', $definition->getType());
        $this->assertEquals(255, $definition->getLength());
    }

    public function test_string_withLength()
    {
        $definition = $this->blueprint->string('email', 45);

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('email', $definition->getName());
        $this->assertEquals('string', $definition->getType());
        $this->assertEquals(45, $definition->getLength());
    }

    public function test_text()
    {
        $definition = $this->blueprint->text('description');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('description', $definition->getName());
        $this->assertEquals('text', $definition->getType());
    }

    public function test_mediumText()
    {
        $definition = $this->blueprint->mediumText('description');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('description', $definition->getName());
        $this->assertEquals('mediumtext', $definition->getType());
    }

    public function test_longText()
    {
        $definition = $this->blueprint->longText('description');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('description', $definition->getName());
        $this->assertEquals('longtext', $definition->getType());
    }

    public function test_timestamp()
    {
        $definition = $this->blueprint->timestamp('created_at');

        $this->assertCount(1, $this->blueprint->getColumnDefinitions());

        $this->assertNotNull($definition);
        $this->assertEquals('created_at', $definition->getName());
        $this->assertEquals('timestamp', $definition->getType());
    }

    public function test_primary()
    {
        $columns = ['id'];
        $this->blueprint->primary($columns, 'test');

        $this->assertCount(1, $this->blueprint->getKeys());
        $key = $this->blueprint->getKeys()[0];
        $this->assertTrue($key instanceof PrimaryKey);
        $this->assertEquals($columns, $key->getColumns());
        $this->assertEquals('test', $key->getName());
    }

    public function test_unique()
    {
        $columns = ['id'];
        $this->blueprint->unique($columns, 'test');

        $this->assertCount(1, $this->blueprint->getKeys());
        $key = $this->blueprint->getKeys()[0];
        $this->assertTrue($key instanceof UniqueKey);
        $this->assertEquals($columns, $key->getColumns());
        $this->assertEquals('test', $key->getName());
    }

    public function test_index()
    {
        $columns = ['id'];
        $this->blueprint->index($columns);

        $this->assertCount(1, $this->blueprint->getKeys());
        $key = $this->blueprint->getKeys()[0];
        $this->assertTrue($key instanceof Index);
        $this->assertEquals($columns, $key->getColumns());
    }

    public function test_foreign()
    {
        $this->blueprint->foreign('user_id', 'id', 'users');

        $this->assertCount(1, $this->blueprint->getKeys());
        $key = $this->blueprint->getKeys()[0];
        $this->assertTrue($key instanceof ForeignKey);
        $this->assertEquals('user_id', $key->getFromColumn());
        $this->assertEquals('id', $key->getToColumn());
        $this->assertEquals('users', $key->getOnTable());
        $this->assertEquals('foreign_user_id_users_id', $key->getName());
    }

}