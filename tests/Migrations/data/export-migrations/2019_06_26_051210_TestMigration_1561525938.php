<?php

use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Migrations\AbstractMigration;

class TestMigration1561525938 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTable('test_export_two', function(Blueprint $blueprint) {
            $blueprint->string('email', 100);
        });
        
        $this->schema->addForeignKey('test_export_two', 'from_column', 'to_column', 'on_table', 'fk_to_drop');

        $this->schema->dropForeignKey('test_export_two', 'fk_to_drop');
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->schema->dropTable('test_export_two');
    }

}