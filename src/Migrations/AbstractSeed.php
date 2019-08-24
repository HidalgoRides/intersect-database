<?php

namespace Intersect\Database\Migrations;

abstract class AbstractSeed {

    public $skipMigration = false;

    abstract public function populate();

}