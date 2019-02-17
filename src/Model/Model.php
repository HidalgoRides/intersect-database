<?php

namespace Intersect\Database\Model;

use Intersect\Database\Query\Query;
use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Exception\ValidationException;

abstract class Model extends AbstractModel implements Extensible {
    
    protected $metaDataColumn = 'meta_data';
    
    private $metaData = null;

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

        $query = QueryBuilder::select($modelClass->getColumnList(), $queryParameters)->table($modelClass->getTableName())->build();
        $result = $modelClass->getConnection()->run($query);

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
     * @param array $properties
     * @return static
     */
    public static function newInstance(array $properties = [])
    {
        $instance = new static();

        $modelAlias = AliasFactory::getAlias($instance->getTableName());

        foreach ($properties as $key => $value)
        {
            $keyParts = explode('.', $key);
            $isAssumedRootAlias = (count($keyParts) == 1);

            if ($isAssumedRootAlias || strpos($key, $modelAlias) === 0)
            {
                if (!$isAssumedRootAlias)
                {
                    $key = substr($key, strlen($modelAlias) + 1);
                }

                $instance->setAttribute($key, $value);
            }
        }

        return $instance;
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

        $value = $this->getAttribute($key);

        return $value;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addMetaData($key, $value)
    {
        $this->metaData[$key] = $value;
    }

    public function clearAllMetaData()
    {
        $metaDataAttribute = (array_key_exists($this->metaDataColumn, $this->attributes)) ? $this->attributes[$this->metaDataColumn] : null;

        if (!is_null($metaDataAttribute))
        {
            $this->attributes[$this->metaDataColumn] = null;
        }

        $this->metaData = null;
    }

    public function clearMetaDataByKey($key)
    {
        if (!is_null($this->metaData))
        {
            if (array_key_exists($key, $this->metaData))
            {
                unset($this->metaData[$key]);
            }
        }
    }

    /**
     * @param array $metaData
     */
    public function setMetaData(array $metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * @return array|null
     */
    public function getMetaData()
    {
        if (is_null($this->metaData))
        {
            $metaDataAttribute = (array_key_exists($this->metaDataColumn, $this->attributes)) ? $this->attributes[$this->metaDataColumn] : null;

            if (!is_null($metaDataAttribute))
            {
                $this->metaData = unserialize($metaDataAttribute);
            }
        }

        return $this->metaData;
    }

    public function getMetaDataByKey($key)
    {
        $metaData = $this->getMetaData();

        if (is_null($metaData))
        {
            return null;
        }

        return (array_key_exists($key, $metaData)) ? $metaData[$key] : null;
    }

    /**
     * @param string $className
     * @param string $column
     * @return static|null
     */
    public function hasOne($className, $column)
    {
        $attributeValue = $this->getAttribute($column);

        if (is_null($attributeValue))
        {
            return null;
        }

        /** @var Model $class */
        $class = new $className();

        $qp = new QueryParameters();
        $qp->equals($class->getPrimaryKey(), $attributeValue);
        return $class::findOne($qp);
    }

    /**
     * @param string $className
     * @param string $column
     * @return static[]
     */
    public function hasMany($className, $column)
    {
        $primaryKeyValue = $this->getPrimaryKeyValue();

        if (is_null($primaryKeyValue))
        {
            return [];
        }

        /** @var Model $class */
        $class = new $className();

        $qp = new QueryParameters();
        $qp->equals($column, $primaryKeyValue);
        return $class::find($qp);
    }

    /**
     * @return static
     * @throws ValidationException
     * @throws DatabaseException
     */
    public function save()
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

        if ($isNewModel)
        {
            $query = QueryBuilder::insert($this->attributes)->table($this->getTableName())->build();
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

            $query = QueryBuilder::update($attributes)->table($this->getTableName())->whereEquals($primaryKey, $primaryKeyValue)->build();
        }

        $result = $this->getConnection()->run($query);

        $savedModel = null;
        $id = ($isNewModel ? $result->getInsertId() : $primaryKeyValue);

        if (!is_null($id))
        {
            $savedModel = $this->findById($id);
        }

        return $savedModel;
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

        $query = QueryBuilder::delete($queryParameters)->table($this->getTableName())->build();

        $result = $this->getConnection()->run($query);

        return ($result->getAffectedRows() == 1);
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getPrimaryKeyValue()
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function normalize($convertAttributeKeys = false)
    {
        $data = $this->attributes;

        foreach ($data as $attributeKey => $attributeValue)
        {
            if ($attributeValue instanceof Model)
            {
                $data[$attributeKey] = $attributeValue->normalize();
            } 
            else if (is_array($attributeValue))
            {
                $normalizeArray = [];
                foreach ($attributeValue as $key => $value)
                {
                    if ($value instanceof Model)
                    {
                        $normalizeArray[$key] = $value->normalize();
                    }
                    else
                    {
                        $normalizeArray[$key] = $value;
                    }
                }
                $data[$attributeKey] = $normalizeArray;
            }
            else 
            {
                if ($convertAttributeKeys)
                {
                    unset($data[$attributeKey]);
                    $camelCaseKey = $this->convertColumnAttributeToCamelCase($attributeKey);
                    $data[$camelCaseKey] = $attributeValue;
                }
            }
        }

        $metaData = $this->getMetaData();
        if (!is_null($metaData))
        {
            $metaDataMap = [];
            foreach ($metaData as $key => $value)
            {
                $metaDataMap[$key] = $value;
            }

            $metaDataKey = (!$convertAttributeKeys ? 'meta_data' : 'metaData');

            $data[$metaDataKey] = $metaDataMap;
        }

        return $data;
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

}