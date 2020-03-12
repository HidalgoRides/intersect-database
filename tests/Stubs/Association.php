<?php

namespace Tests\Stubs;

use Intersect\Database\Model\AssociativeModel;

class Association extends AssociativeModel {

    public function getColumnOneClassName()
    {
        return null;
    }

    public function getColumnOneName()
    {
        return 'key_one';
    }

    public function getColumnTwoClassName()
    {
        return null;
    }

    public function getColumnTwoName()
    {
        return 'key_two';
    }

}