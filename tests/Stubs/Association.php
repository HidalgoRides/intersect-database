<?php

namespace Tests\Stubs;

use Intersect\Database\Model\AssociativeModel;

class Association extends AssociativeModel {

    protected function getColumnOneName()
    {
        return 'key_one';
    }

    protected function getColumnTwoName()
    {
        return 'key_two';
    }

}