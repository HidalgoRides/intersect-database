<?php

namespace Tests\Stubs;

use Intersect\Database\Model\Model;

class PartialAssociation extends Model {

    protected $primaryKey = 'association_id';
    protected $tableName = 'associations';

}