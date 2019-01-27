<?php

namespace Intersect\Database\Exception;

class OperationNotSupportedException extends \Exception {

    public function __construct($operation)
    {
        parent::__construct('Operation not supported: ' . $operation);
    }

}