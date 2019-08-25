<?php

use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Migrations\AbstractMigration;

class MigrationTableV11533081600 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTableIfNotExists('ic_migrations', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('name');
            $blueprint->string('path');
            $blueprint->tinyInteger('status')->default(1);
            $blueprint->integer('batch_id');
            $blueprint->datetime('date_created');
            $blueprint->datetime('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        // nothing to do here since we do not want to drop the migrations table once it is created
    }

}