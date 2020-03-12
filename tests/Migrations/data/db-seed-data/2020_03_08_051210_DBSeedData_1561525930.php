<?php

use Intersect\Database\Connection\PostgresConnection;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Migrations\AbstractMigration;
use Tests\Stubs\Address;
use Tests\Stubs\Association;
use Tests\Stubs\Name;
use Tests\Stubs\Phone;
use Tests\Stubs\Unit;
use Tests\Stubs\User;

class DBSeedData1561525930 extends AbstractMigration {

    /**
     * Run the migration
     */
    public function up()
    {
        $this->schema->createTable('users', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('email', 50);
            $blueprint->string('password', 50)->nullable();
            $blueprint->tinyInteger('status')->nullable();
            $blueprint->integer('phone_id')->nullable();
            $blueprint->integer('name_id')->nullable();
            $blueprint->text('meta_data')->nullable();
            $blueprint->temporal();
        });

        $this->schema->createTableIfNotExists('phones', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('number', 15);
        });

        $this->schema->createTableIfNotExists('names', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('first_name', 20);
            $blueprint->string('last_name', 20);
        });

        $this->schema->createTableIfNotExists('user_name_associations', function(Blueprint $blueprint) {
            $blueprint->integer('user_id');
            $blueprint->integer('name_id');
            $blueprint->primary(['user_id', 'name_id']);
        });

        $this->schema->createTableIfNotExists('addresses', function(Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->integer('user_id');
            $blueprint->string('street', 100);
            $blueprint->string('city', 100);
            $blueprint->string('state', 5);
            $blueprint->string('zip_code', 20);
        });

        $this->schema->createTableIfNotExists('associations', function(Blueprint $blueprint) {
            $blueprint->integer('association_id')->nullable();
            $blueprint->integer('key_one');
            $blueprint->integer('key_two');
            $blueprint->string('data', 100)->nullable();

            $blueprint->primary(['key_one', 'key_two']);
        });

        Name::bulkCreate([
            ['first_name' => 'Unit', 'last_name' => 'Test']
        ]);

        Phone::bulkCreate([
            ['number' => '15551234567']
        ]);

        User::bulkCreate([
            ['email' => 'unit-test@test.com', 'phone_id' => 1, 'name_id' => 1]
        ]);

        Address::bulkCreate([
            ['user_id' => 1, 'street' => 'Test Street 1', 'city' => 'Test City 1', 'state' => 'IT', 'zip_code' => 12345],
            ['user_id' => 1, 'street' => 'Test Street 2', 'city' => 'Test City 2', 'state' => 'IT', 'zip_code' => 67890]
        ]);

        Association::bulkCreate([
            ['key_one' => 1, 'key_two' => 1],
            ['key_one' => 1, 'key_two' => 2],
            ['key_one' => 2, 'key_two' => 1]
        ]);

        if ($this->schema->getConnection() instanceof PostgresConnection)
        {
            $this->schema->getConnection()->query("create schema test;");
            $this->schema->getConnection()->getConnectionSettings()->setSchema('test');

            $this->schema->createTableIfNotExists('units', function(Blueprint $blueprint) {
                $blueprint->increments('id');
                $blueprint->string('number', 15);
            });

            Unit::bulkCreate([
                ['number' => '20']
            ]);

            $this->schema->getConnection()->getConnectionSettings()->setSchema('public');
        }
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        if ($this->schema->getConnection() instanceof PostgresConnection)
        {
            $this->schema->getConnection()->getConnectionSettings()->setSchema('test');
            $this->schema->dropTableIfExists('units');
            $this->schema->getConnection()->getConnectionSettings()->setSchema('public');
        }
        
        $this->schema->dropTableIfExists('associations');
        $this->schema->dropTableIfExists('addresses');
        $this->schema->dropTableIfExists('names');
        $this->schema->dropTableIfExists('phones');
        $this->schema->dropTableIfExists('users');
    }

}