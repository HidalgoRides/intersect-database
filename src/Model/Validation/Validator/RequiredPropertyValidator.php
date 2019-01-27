<?php

namespace Intersect\Database\Model\Validation\Validator;

class RequiredPropertyValidator implements PropertyValidator {

    public function getMessage($property)
    {
        return $property . ' is required';
    }

    public function validate($value)
    {
        if (is_null($value) || trim($value) == '')
        {
            return false;
        }

        return true;
    }

}