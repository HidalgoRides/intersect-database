<?php

namespace Tests\Stubs;

use Tests\Stubs\Name;
use Tests\Stubs\Phone;
use Intersect\Database\Model\TemporalModel;

class User extends TemporalModel {

    protected $connectionKey = 'users';

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

    // method expose for testing protected method functionality
    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $value);
    }

}