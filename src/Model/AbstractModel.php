<?php

namespace Intersect\Database\Model;

use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Model\AssociativeModel;
use Intersect\Database\Query\ModelAliasFactory;
use Intersect\Database\Model\Traits\HasMetaData;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Model\Validation\Validation;
use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Connection\ConnectionRepository;
use Intersect\Database\Model\Validation\ModelValidator;

abstract class AbstractModel implements ModelActions {
    use HasMetaData;

    private static $COLUMN_LIST_CACHE = [];

    protected $attributes = [];
    protected $columns = [];
    protected $connectionKey = 'default';
    protected $forceCreate = false;
    protected $isDirty = false;
    protected $primaryKey = 'id';
    protected $readOnlyAttributes = [];
    /** @var static[] */
    protected $relationships = [];
    protected $schema;
    protected $tableName;

    /** @var Connection */
    private $connection;

    abstract public function delete();
    abstract public function save();

    public function __construct()
    {
        $this->tableName = $this->getTableName();
    }

    public static function bulkCreate(array $modelData)
    {
        $models = [];

        foreach ($modelData as $data)
        {
            $model = self::newInstance($data);
            $model->forceCreate = true;
            $models[] = $model->save(true);
        }

        return $models;
    }

    /**
     * @param QueryParameters|null $queryParameters
     * @return int
     * @throws DatabaseException
     */
    public static function count(QueryParameters $queryParameters = null)
    {
        $modelClass = new static();

        $queryBuilder = $modelClass->getConnection()->getQueryBuilder();
        $result = $queryBuilder->table($modelClass->getTableName(), $modelClass->getPrimaryKey())
            ->schema($modelClass->getSchema())
            ->count($queryParameters)
            ->get();

        return (int) $result->getFirstRecord()['count'];
    }

    /**
     * @param QueryParameters|null $queryParameters
     * @return static[]
     * @throws DatabaseException
     */
    public static function find(QueryParameters $queryParameters = null)
    {
        $modelClass = new static();
        return $modelClass->findInstances($queryParameters);
    }

    /**
     * @param QueryParameters|null $queryParameters
     * @return mixed|null
     * @throws DatabaseException
     */
    public static function getMaxValue($column, QueryParameters $queryParameters = null)
    {
        $modelClass = new static();

        $queryBuilder = $modelClass->getConnection()->getQueryBuilder();
        $result = $queryBuilder->table($modelClass->getTableName())->selectMax($column, $queryParameters)->get();

        return $result->getFirstRecord()['max_value'];
    }

    /**
     * @param array $properties
     * @return static
     */
    public static function newInstance(array $properties = [])
    {
        $instance = new static();

        $modelAlias = ModelAliasFactory::generateAlias($instance);

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

        $instance->isDirty = false;

        return $instance;
    }

    /**
     * @return bool
     */
    public static function truncate()
    {
        $instance = new static();
        $instance->getConnection()->getQueryBuilder()->truncateTable($instance->getTableName())->get();
        return true;
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
     * @param $callable
     * @param $fallback
     */
    public static function withTransaction(callable $callable, callable $fallback = null)
    {
        $instance = new static();
        $connection = $instance->getConnection();

        try {
            $connection->startTransaction();
            $callable();
            $connection->commitTransaction();
        } catch (\Exception $e) {
            $connection->rollbackTransaction();

            if (!is_null($fallback))
            {
                $fallback($e);
            }
        }
        
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function getColumnList()
    {
        if (!is_null($this->columns) && count($this->columns) > 0)
        {
            $columnList = $this->columns;
        }
        else
        {
            $cacheKey = get_class($this);

            if (array_key_exists($cacheKey, self::$COLUMN_LIST_CACHE))
            {
                return self::$COLUMN_LIST_CACHE[$cacheKey];
            }

            $queryBuilder = $this->getConnection()->getQueryBuilder();
            $result = $queryBuilder->columns()->table($this->tableName, $this->primaryKey)->schema($this->schema)->get();

            $columnList = [];

            if ($result->getCount() > 0)
            {
                foreach ($result->getRecords() as $record)
                {
                    $columnList[] = $record['Field'];
                }
            }

            self::$COLUMN_LIST_CACHE[$cacheKey] = $columnList;
        }

        return $columnList;
    }

    /**
     * @return Connection
     * @throws \Exception
     */
    public function getConnection()
    {
        if (is_null($this->connection))
        {
            $connection = ConnectionRepository::get($this->connectionKey);

            if (is_null($connection))
            {
                throw new \Exception('Connection could not be found for key: ' . $this->connectionKey . '. Please ensure you register this connection.');
            }

            $this->connection = $connection;
        }

        return $this->connection;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getPrimaryKeyValue()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * @return array
     */
    public function getReadOnlyAttributes()
    {
        return $this->readOnlyAttributes;
    }

    /** 
     * @return static|null 
     */
    public function getRelationship($key)
    {
        return (array_key_exists($key, $this->relationships)) ? $this->relationships[$key] : null;
    }

    /** 
     * @return static[] 
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @return mixed|string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return mixed|string
     */
    public function getTableName()
    {
        if (is_null($this->tableName))
        {
            $this->tableName = $this->resolveTableName(get_called_class());
        }

        return $this->tableName;
    }

    /**
     * @param string $className
     * @param string $joiningClassColumn
     * @param QueryParameters $queryParameters
     * @return static[]
     */
    public function hasMany($joiningClassName, $joiningClassColumn, QueryParameters $queryParameters = null)
    {
        $primaryKeyValue = $this->getPrimaryKeyValue();

        if (is_null($primaryKeyValue))
        {
            return [];
        }

        /** @var AbstractModel|AssociativeModel|null $joiningClass */
        $joiningClass = new $joiningClassName();
        $joiningTableAlias = ModelAliasFactory::generateAlias($joiningClass);
        $joiningClassColumnValues = [$primaryKeyValue];

        if ($joiningClass instanceof AssociativeModel)
        {
            $pivotClassName = null;
            $pivotClassColumn = null;
            $associations = [];

            if ($joiningClass->getColumnOneName() == $joiningClassColumn)
            {
                $pivotClassName = $joiningClass->getColumnTwoClassName();
                $pivotClassColumn = $joiningClass->getColumnTwoName();

                $associations = $joiningClass::findAssociationsForColumnOne($this->getPrimaryKeyValue());
            } 
            else if ($joiningClass->getColumnTwoName() == $joiningClassColumn)
            {
                $pivotClassName = $joiningClass->getColumnOneClassName();
                $pivotClassColumn = $joiningClass->getColumnOneName();

                $associations = $joiningClass::findAssociationsForColumnTwo($this->getPrimaryKeyValue());
            }

            if (is_null($pivotClassName) || is_null($pivotClassColumn))
            {
                return [];
            }

            $associationIds = [];

            foreach ($associations as $association)
            {
                $associationIds[] = $association->{$pivotClassColumn};
            }

            if (count($associationIds) == 0)
            {
                return [];
            }

            $joiningClass = new $pivotClassName();
            $joiningClassColumn = $joiningClass->getPrimaryKey();
            $joiningClassColumnValues = $associationIds;
        }

        $queryBuilder = $joiningClass->getConnection()->getQueryBuilder();
        $queryBuilder
            ->select($joiningClass->getColumnList(), $queryParameters)
            ->table($joiningClass->getTableName, $joiningClass->getPrimaryKey(), $joiningTableAlias)
            ->schema($joiningClass->getSchema())
            ->whereIn($joiningClassColumn, $joiningClassColumnValues);

        return $queryBuilder;
    }

    /**
     * @param string $className
     * @param string $sourceClassColumn
     * @param QueryParameters $queryParameters
     * @return static|null
     */
    public function hasOne($joiningClassName, $sourceClassColumn, QueryParameters $queryParameters = null)
    {
        /** @var AbstractModel|AssociativeModel|null $joiningClass */
        $joiningClass = new $joiningClassName();
        $joiningClassIsAssociativeModel = ($joiningClass instanceof AssociativeModel);
        
        $joiningClassColumnValue = $this->getAttribute($sourceClassColumn);

        if (!$joiningClassIsAssociativeModel && is_null($joiningClassColumnValue))
        {
            return null;
        }

        $joiningTableAlias = ModelAliasFactory::generateAlias($joiningClass);

        if ($joiningClassIsAssociativeModel) 
        {
            $pivotClassName = null;
            $pivotClassColumn = null;
            $associations = [];
    
            if ($joiningClass->getColumnOneName() == $sourceClassColumn)
            {
                $pivotClassName = $joiningClass->getColumnTwoClassName();
                $pivotClassColumn = $joiningClass->getColumnTwoName();
    
                $associationCallback = function() use ($joiningClass) {
                    return $joiningClass::findAssociationsForColumnOne($this->getPrimaryKeyValue());
                };
            } 
            else if ($joiningClass->getColumnTwoName() == $sourceClassColumn)
            {
                $pivotClassName = $joiningClass->getColumnOneClassName();
                $pivotClassColumn = $joiningClass->getColumnOneName();
    
                $associationCallback = function() use ($joiningClass) {
                    return $joiningClass::findAssociationsForColumnTwo($this->getPrimaryKeyValue());
                };
            }
    
            if (is_null($pivotClassName) || is_null($pivotClassColumn))
            {
                return null;
            }

            $associations = $associationCallback();
            $associationIds = [];

            foreach ($associations as $association)
            {
                $associationIds[] = $association->{$pivotClassColumn};
            }

            if (count($associationIds) == 0)
            {
                return null;
            }

            $joiningClass = new $pivotClassName();
            $joiningClassColumnValue = $associationIds[0];
        }

        $queryBuilder = $joiningClass->getConnection()->getQueryBuilder();
        $queryBuilder
            ->select($joiningClass->getColumnList(), $queryParameters)
            ->table($joiningClass->getTableName, $joiningClass->getPrimaryKey(), $joiningTableAlias)
            ->schema($joiningClass->getSchema())
            ->whereEquals($joiningClass->getPrimaryKey(), $joiningClassColumnValue)
            ->limit(1);

        return $queryBuilder;
    }

    /**
     * @return bool
     */
    public function isDirty()
    {
        $isDirty = $this->isDirty;

        if (!$isDirty)
        {
            foreach ($this->relationships as $relationship)
            {
                if (is_null($relationship))
                {
                    continue;
                }

                $isDirty = $relationship->isDirty();
                if ($isDirty)
                {
                    break;
                }
            }
        }

        return $isDirty;
    }

    /**
     * @return array
     */
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

    /**
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        if ($key == $this->metaDataColumn)
        {
            return $this->getMetaData();
        }
        
        $value = $this->getAttribute($key);

        if (is_null($value))
        {
            if (isset($this->relationships[$key]))
            {
                $value = $this->relationships[$key];
            }
            else if (method_exists($this, $key))
            {
                $value = $this->{$key}();

                if (!is_null($value) && $value instanceof QueryBuilder)
                {
                    $value = $this->convertFromQueryBuilder($value);
                }

                $this->relationships[$key] = $value;
            }
        }

        return $value;
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return (!is_null($this->__get($key)));
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        if (method_exists($this, $key))
        {
            $this->relationships[$key] = $value;
            if ($value instanceof AbstractModel)
            {
                $value->isDirty = true;
            }
        }
        else
        {
            $this->setAttribute($key, $value);
        }

        $this->isDirty = true;
    }

    /**
     * @param QueryParameters $queryParameters
     * @return static[]
     */
    protected function findInstances(QueryParameters $queryParameters = null) 
    {
        $isColumnOverride = (!is_null($queryParameters) && count($queryParameters->getColumns()) > 0);

        if ($isColumnOverride)
        {
            $this->columns = $queryParameters->getColumns();
        }

        $tableAlias = ModelAliasFactory::generateAlias($this);
        $queryBuilder = $this->getConnection()->getQueryBuilder();
        $result = $queryBuilder->table($this->getTableName(), $this->getPrimaryKey(), $tableAlias)
            ->schema($this->getSchema())
            ->select($this->getColumnList(), $queryParameters)
            ->get();

        $models = [];

        foreach ($result->getRecords() as $record)
        {
            $models[] = self::newInstance($record);
        }

        return $models;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    protected function getAttribute($key)
    {
        $attribute = null;

        if (isset($this->attributes[$key]))
        {
            $attribute = $this->attributes[$key];
        }

        return $attribute;
    }

    /**
     * @param $key
     * @param $value
     */
    protected function setAttribute($key, $value)
    {
        $this->isDirty = true;
        $this->attributes[$key] = $value;
    }

    /**
     * @throws ValidationException
     */
    protected function validate()
    {
        if ($this instanceof Validation)
        {
            $modelValidator = new ModelValidator();
            $modelValidator->validate($this, $this->getValidatorMap());
        }
    }

    private function convertFromQueryBuilder(QueryBuilder $queryBuilder)
    {
        $modelAlias = ModelAliasFactory::getAliasValue($queryBuilder->getAlias());
        $modelClassName = $modelAlias->getModelClassName();
        $result = $queryBuilder->get();

        $convertedValue = null;

        if ($queryBuilder->getLimit() == 1)
        {
            $record = $result->getFirstRecord();
            if (!is_null($record))
            {
                $convertedValue = $modelClassName::newInstance($record);
            }
        }
        else
        {
            $models = [];

            foreach ($result->getRecords() as $record)
            {
                $models[] = $modelClassName::newInstance($record);
            }

            $convertedValue = $models;
        }

        return $convertedValue;
    }

    private function convertColumnAttributeToCamelCase($string)
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    private function normalizeData(array &$normalizedData, array $dataToNormalize, $convertKeys = false)
    {
        foreach ($dataToNormalize as $key => $value)
        {
            $key = ($convertKeys) ? $this->convertColumnAttributeToCamelCase($key) : $key;
            
            if ($value instanceof AbstractModel)
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
                $normalizedData[$key] = $value;
            }
        }
    }

    /**
     * @param $s
     * @return string
     */
    private function pluralizeTableName($s)
    {
        $lastCharacter = substr($s, -1);
        $pluralLetter = 's';

        if ($lastCharacter == 'y')
        {
            $secondToLastCharacter = substr($s, -2, 1);
            if (!preg_match('/^[aeiou]/i', $secondToLastCharacter))
            {
                $pluralLetter = 'ies';
            }

            $s = substr($s, 0, -1) . $pluralLetter;
        }
        else if ($lastCharacter == 's')
        {
            $s = $s . 'es';
        }
        else
        {
            $s = $s . $pluralLetter;
        }

        return $s;
    }

    /**
     * @param $className
     * @return string
     */
    private function resolveTableName($className)
    {
        $classNameParts = explode('\\', $className);
        $className = end($classNameParts);

        if (!ctype_lower($className))
        {
            $className = preg_replace('/\s+/u', '', ucwords($className));
            $className = $this->pluralizeTableName(strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $className)));
        }

        return $className;
    }

}