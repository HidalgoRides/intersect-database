<?php

use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Migrations\AbstractMigration;

class TestMigration1561525930 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTable('test_migration_one', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('email', 100);
        });
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->schema->dropTable('test_migration_one');
    }

}