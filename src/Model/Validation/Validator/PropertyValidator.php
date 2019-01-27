<?php

namespace Intersect\Database\Model\Validation\Validator;

interface PropertyValidator {

    public function getMessage($property);

    public function validate($value);

}