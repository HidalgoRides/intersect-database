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
    public function save($forceSave = false)
    {
        if (!$this->isDirty() && !$forceSave)
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

        return parent::save($forceSave);
    }

    public function getDateCreated()
    {
        return $this->getAttribute($this->dateCreatedColumn);
    }

    public function getDateUpdated()
    {
        return $this->getAttribute($this->dateUpdatedColumn);
    }

    private function setTemporalAttribute($columnAttributeName)
    {
        if (!is_null($columnAttributeName) && trim($columnAttributeName) != '')
        {
            $this->setAttribute($columnAttributeName, date('Y-m-d H:i:s'));
        }
    }

}