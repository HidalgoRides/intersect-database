<?php

namespace Intersect\Database\Model;

use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Exception\ValidationException;

abstract class TemporalModel extends Model {

    protected $dateCreatedColumn = 'date_created';
    protected $dateUpdatedColumn = 'date_updated';

    /**
     * @return static
     * @throws ValidationException
     * @throws DatabaseException
     */
    public function save()
    {
        if (!$this->isDirty())
        {
            return $this;
        }

        if ($this->isNewModel())
        {
            $this->setTemporalAttribute($this->dateCreatedColumn);
        }
        else
        {
            $this->setTemporalAttribute($this->dateUpdatedColumn);
        }

        return parent::save();
    }

    private function setTemporalAttribute($columnAttributeName)
    {
        if (!is_null($columnAttributeName) && trim($columnAttributeName) != '')
        {
            $this->setAttribute($columnAttributeName, date('Y-m-d H:i:s'));
        }
    }

}