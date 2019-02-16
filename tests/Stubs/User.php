<?php

namespace Tests\Stubs;

use Tests\Stubs\Name;
use Tests\Stubs\Phone;
use Intersect\Database\Model\Model;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Model\Relationship\Relational;
use Intersect\Database\Model\Relationship\EagerRelationship;

class User extends Model implements Relational {

    public function getEagerRelationshipMap()
    {
        return [
            new EagerRelationship(Phone::class, 'phone_id', 'phone')
        ];
    }

    public function name()
    {
        return $this->hasOne(Name::class, 'name_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

}