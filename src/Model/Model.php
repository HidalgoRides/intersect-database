<?php

namespace Intersect\Database\Model;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Model\Traits\HasMetaData;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Query\ModelAliasFactory;

abstract class Model extends AbstractModel {
    use HasMetaData;

    /**
     * @param QueryParameters|null $queryParameters
     * @return static[]
     * @throws DatabaseException
     */
    public static function find(QueryParameters $queryParameters = null)
    {
        $modelClass = new static();
        $isColumnOverride = (!is_null($queryParameters) && count($queryParameters->getColumns()) > 0);

        if ($isColumnOverride)
        {
            $modelClass->columns = $queryParameters->getColumns();
        }

        $tableAlias = ModelAliasFactory::generateAlias($modelClass);
        $queryBuilder = new QueryBuilder($modelClass->getConnection());
        $result = $queryBuilder->table($modelClass->getTableName(), $tableAlias)
            ->select($modelClass->getColumnList(), $queryParameters)
            ->get();

        $models = [];

        foreach ($result->getRecords() as $record)
        {
            $models[] = self::newInstance($record);
        }

        return $models;
    }

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

        $models = self::find($queryParameters);

        if (count($models) == 1)
        {
            $model = $models[0];
        }

        return $model;
    }

    /**
     * @return static[]
     */
    public static function with(array $methodNames, QueryParameters $queryParameters = null)
    {
        $models = self::find($queryParameters);

        foreach ($models as $model)
        {
            foreach ($methodNames as $methodName)
            {
                if (method_exists($model, $methodName))
                {
                    $model->{$methodName};
                }
            }
        }

        return $models;
    }

    /**
     * @param $key
     * @return mixed|null
     * @throws DatabaseException
     */
    public function __get($key)
    {
        if ($key == $this->metaDataColumn)
        {
            return $this->getMetaData();
        }

        return parent::__get($key);
    }

    /**
     * @param string $className
     * @param string $column
     * @return static|null
     */
    public function hasOne($joiningClassName, $column)
    {
        $attributeValue = $this->getAttribute($column);

        if (is_null($attributeValue))
        {
            return null;
        }

        /** @var Model $joiningClass */
        $joiningClass = new $joiningClassName();
        $joiningTableAlias = ModelAliasFactory::generateAlias($joiningClass);

        $queryBuilder = new QueryBuilder($joiningClass->getConnection());
        $queryBuilder
            ->select($joiningClass->getColumnList())
            ->table($joiningClass->getTableName, $joiningTableAlias)
            ->whereEquals($joiningClass->getPrimaryKey(), $attributeValue)
            ->limit(1);

        return $queryBuilder;
    }

    /**
     * @param string $className
     * @param string $column
     * @return static[]
     */
    public function hasMany($joiningClassName, $column)
    {
        $primaryKeyValue = $this->getPrimaryKeyValue();

        if (is_null($primaryKeyValue))
        {
            return [];
        }

        /** @var Model $joiningClass */
        $joiningClass = new $joiningClassName();
        $joiningTableAlias = ModelAliasFactory::generateAlias($joiningClass);

        $queryBuilder = new QueryBuilder($joiningClass->getConnection());
        $queryBuilder
            ->select($joiningClass->getColumnList())
            ->table($joiningClass->getTableName, $joiningTableAlias)
            ->whereEquals($column, $primaryKeyValue);

        return $queryBuilder;
        
        $models = [];
        
        foreach ($queryBuilder->get()->getRecords() as $record)
        {
            $models[] = $joiningClassName::newInstance($record);
        }

        return $models;
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

        $queryBuilder = new QueryBuilder($this->getConnection());
        $result = $queryBuilder->delete($queryParameters)->table($this->getTableName())->get();

        return ($result->getAffectedRows() == 1);
    }

    public function normalize($convertAttributeKeys = false)
    {
        $normalizedData = [];

        $this->normalizeData($normalizedData, $this->attributes, $convertAttributeKeys);
        $this->normalizeData($normalizedData, $this->relationships, $convertAttributeKeys);

        $metaData = $this->getMetaData();
        if (!is_null($metaData))
        {
            $metaDataKey = $convertAttributeKeys ? $this->convertColumnAttributeToCamelCase($this->metaDataColumn) : $this->metaDataColumn;

            $normalizedData[$metaDataKey] = [];
            $this->normalizeData($normalizedData[$metaDataKey], $metaData, $convertAttributeKeys);
        }

        return $normalizedData;
    }

    protected function isNewModel()
    {
        $primaryKeyValue = $this->getPrimaryKeyValue();
        return is_null($primaryKeyValue);
    }

    private function convertColumnAttributeToCamelCase($string)
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    private function normalizeData(array &$normalizedData, array $dataToNormalize, $convertKeys = false)
    {
        foreach ($dataToNormalize as $key => $value)
        {
            if ($value instanceof Model)
            {
                $normalizedData[$key] = $value->normalize($convertKeys);
            } 
            else if (is_array($value))
            {
                $normalizedData[$key] = [];
                $this->normalizeData($normalizedData[$key], $value, $convertKeys);
            }
            else 
            {
                $key = ($convertKeys) ? $this->convertColumnAttributeToCamelCase($key) : $key;
                $normalizedData[$key] = $value;
            }
        }
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

        $queryBuilder = new QueryBuilder($this->getConnection());

        if ($isNewModel)
        {
            $queryBuilder->insert($this->attributes)->table($this->getTableName());
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

            $queryBuilder->update($attributes)->table($this->getTableName())->whereEquals($primaryKey, $primaryKeyValue);
        }

        $result = $queryBuilder->get();

        $id = ($isNewModel ? $result->getInsertId() : $primaryKeyValue);

        if (!is_null($id))
        {
            $savedModel = $this->findById($id);

            $this->attributes = $savedModel->attributes;
            $this->isDirty = false;
        }

        return $this;
    }

}