<?php

namespace Intersect\Database\Model;

use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Query\ModelAliasFactory;

abstract class Model extends AbstractModel {

    /**
     * @param QueryParameters|null $queryParameters
     * @return static|null
     * @throws DatabaseException
     */
    public static function findOne(QueryParameters $queryParameters = null)
    {
        if (is_null($queryParameters))
        {
            $queryParameters = new QueryParameters();
        }

        $queryParameters->setLimit(1);

        $models = self::find($queryParameters);
        $model = null;

        if (count($models) > 0)
        {
            return $models[0];
        }

        return $model;
    }

    /**
     * @param $id
     * @return static|null
     * @throws DatabaseException
     */
    public static function findById($id)
    {
        /** @var Model $modelClass */
        $modelClass = new static();
        $model = null;

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getPrimaryKey(), $id);
        $queryParameters->setLimit(1);

        $models = $modelClass->findInstances($queryParameters);

        if (count($models) == 1)
        {
            $model = $models[0];
        }

        return $model;
    }

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

        if ($forceSave || $this->isDirty)
        {
            $this->performSave();
        }

        foreach ($this->relationships as $relationship)
        {
            if ($forceSave || $relationship->isDirty())
            {
                $relationship->performSave();
            }
        }

        return $this;
    }

    /**
     * @return bool
     * @throws DatabaseException
     */
    public function delete()
    {
        $primaryKeyValue = $this->getPrimaryKeyValue();

        if (is_null($primaryKeyValue))
        {
            return false;
        }

        $queryParameters = new QueryParameters();
        $queryParameters->equals($this->getPrimaryKey(), $primaryKeyValue);
        $queryParameters->setLimit(1);

        $queryBuilder = $this->getConnection()->getQueryBuilder();
        $result = $queryBuilder->delete($queryParameters)->table($this->tableName, $this->primaryKey)->schema($this->schema)->get();

        return ($result->getAffectedRows() == 1);
    }

    protected function isNewModel()
    {
        $primaryKeyValue = $this->getPrimaryKeyValue();
        return (is_null($primaryKeyValue) || $this->forceCreate);
    }

    /**
     * @return static
     */
    private function performSave()
    {
        $this->validate();

        $primaryKeyValue = $this->getPrimaryKeyValue();
        $isNewModel = $this->isNewModel();

        $metaData = $this->getMetaData();
        if (!is_null($metaData))
        {
            $this->{$this->metaDataColumn} = serialize($this->getMetaData());
        }
        else
        {
            unset($this->{$this->metaDataColumn});
        }

        $queryBuilder = $this->getConnection()->getQueryBuilder();

        if ($isNewModel)
        {
            $queryBuilder->insert($this->attributes)->table($this->tableName, $this->primaryKey);
        }
        else
        {
            $attributes = $this->attributes;
            $primaryKey = $this->getPrimaryKey();

            if (count($this->readOnlyAttributes) > 0)
            {
                $attributes = array_diff_key($this->attributes, array_flip($this->readOnlyAttributes));
            }

            if (array_key_exists($primaryKey, $attributes))
            {
                unset($attributes[$primaryKey]);
            }

            $queryBuilder->update($attributes)->table($this->tableName, $this->primaryKey)->whereEquals($primaryKey, $primaryKeyValue);
        }

        $result = $queryBuilder->schema($this->schema)->get();

        $id = (int) ($isNewModel ? $result->getInsertId() : $primaryKeyValue);

        if ($isNewModel)
        {
            $this->{$this->primaryKey} = $id;
        }

        if (!is_null($id) && $id > 0)
        {
            $savedModel = $this->findById($id);

            if (!is_null($savedModel))
            {
                $savedModelAttributes = $savedModel->attributes;

                $this->attributes = (!is_null($savedModelAttributes)) ? $savedModelAttributes : $this->attributes;
            }
        }
        
        $this->isDirty = false;
        

        return $this;
    }

}