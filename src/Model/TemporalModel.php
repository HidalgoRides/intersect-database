<?php

namespace Intersect\Database\Model;

abstract class TemporalModel extends Model {

    protected $dateCreatedColumn = 'date_created';
    protected $dateUpdatedColumn = 'date_updated';

    public function save()
    {
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