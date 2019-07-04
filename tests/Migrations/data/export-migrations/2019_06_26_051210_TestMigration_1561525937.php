<?php

use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Migrations\AbstractMigration;

class TestMigration1561525937 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTable('test_export_two', function(Blueprint $blueprint) {
            $blueprint->string('email', 100);
        });
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->schema->dropTable('test_export_two');
    }

}