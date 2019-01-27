<?php

namespace Intersect\Database\Exception;

use Intersect\Database\Model\AbstractModel;

class ValidationException extends \Exception {

    private $reasons = [];

    public function __construct(AbstractModel $model, array $reasons = [])
    {
        parent::__construct('Validation failed for model: ' . get_class($model) . '<br /> - ' . implode(' <br /> - ', $reasons));
        $this->reasons = $reasons;
    }

    public function getReasons()
    {
        return $this->reasons;
    }

}