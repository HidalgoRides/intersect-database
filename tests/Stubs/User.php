<?php

namespace Tests\Stubs;

use Tests\Stubs\Name;
use Tests\Stubs\Phone;
use Intersect\Database\Model\Model;
use Intersect\Database\Query\QueryParameters;

class User extends Model {

    public function phone()
    {
        return $this->hasOne(Phone::class, 'phone_id');
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