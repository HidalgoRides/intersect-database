<?php

use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Migrations\AbstractMigration;
use Intersect\Database\Schema\ColumnBlueprint;

class TestMigrationRollback1561525930 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTable('test_migration_rollback', function(Blueprint $blueprint) {
            $blueprint->increments('id');
        });

        $this->schema->addColumn('table_does_not_exist', new ColumnBlueprint());
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        $this->schema->dropTableIfExists('test_migration_rollback');
    }

}