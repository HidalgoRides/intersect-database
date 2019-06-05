<?php

namespace Intersect\Database\Model;

use Intersect\Database\Query\ModelAliasFactory;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\Builder\QueryBuilder;
use Intersect\Database\Model\Validation\Validation;
use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Model\Validation\ModelValidator;
use Intersect\Database\Connection\ConnectionRepository;

abstract class AbstractModel implements ModelActions {

    private static $COLUMN_LIST_CACHE = [];

    protected $attributes = [];
    protected $columns = [];
    protected $connectionKey = 'default';
    protected $isDirty = false;
    protected $primaryKey = 'id';
    protected $readOnlyAttributes = [];
    /** @var static[] */
    protected $relationships = [];
    protected $tableName;

    /** @var Connection */
    private $connection;

    abstract public function delete();
    abstract public function save();

    public function __construct()
    {
        $this->tableName = $this->getTableName();
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
     * @return array|mixed
     * @throws \Exception
     */
    public function getColumnList()
    {
        if (!is_null($this->columns) && count($this->columns) > 0)
        {
            $columnList = $this->columns;

            if (!in_array($this->primaryKey, $columnList))
            {
                $columnList[] = $this->primaryKey;
            }
        }
        else
        {
            $cacheKey = get_class($this);

            if (array_key_exists($cacheKey, self::$COLUMN_LIST_CACHE))
            {
                return self::$COLUMN_LIST_CACHE[$cacheKey];
            }

            $queryBuilder = $this->getConnection()->getQueryBuilder();
            $result = $queryBuilder->columns()->table($this->tableName, $this->primaryKey)->get();

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
    public function getTableName()
    {
        if (is_null($this->tableName))
        {
            $this->tableName = $this->resolveTableName(get_called_class());
        }

        return $this->tableName;
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
    public function normalize()
    {
        return $this->attributes;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
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