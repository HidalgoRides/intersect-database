<?php

namespace Tests\Stubs;

use Intersect\Database\Model\AssociativeModel;

class UserNameAssociation extends AssociativeModel {

    public function getColumnOneClassName()
    {
        return User::class;
    }

    public function getColumnOneName()
    {
        return 'user_id';
    }

    public function getColumnTwoClassName()
    {
        return Name::class;
    }

    public function getColumnTwoName()
    {
        return 'name_id';
    }

}