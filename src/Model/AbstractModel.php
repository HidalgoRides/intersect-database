<?php

namespace Intersect\Database\Model;

use Intersect\Core\Container;
use Intersect\Core\ClassResolver;
use Intersect\Core\Registry\ClassRegistry;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Model\Validation\Validation;
use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Model\Validation\ModelValidator;
use Intersect\Database\Query\Builder\DefaultQueryBuilder;
use Intersect\Database\Connection\NullConnection;

abstract class AbstractModel implements ModelActions {

    private static $COLUMN_LIST_CACHE = [];
    private static $CONNECTION;

    protected $attributes = [];
    protected $columns = [];
    protected $primaryKey = 'id';
    protected $readOnlyAttributes = [];
    protected $tableName;

    abstract public function delete();
    abstract public function save();

    public function __construct()
    {
        $this->tableName = $this->getTableName();
    }

    /**
     * @param array $properties
     * @return AbstractModel
     */
    public static function newInstance(array $properties = [])
    {
        $instance = new static();

        foreach ($properties as $key => $value)
        {
            $instance->setAttribute($key, $value);
        }

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

            $queryBuilder = new DefaultQueryBuilder($this->tableName);
            $result = $this->getConnection()->run($queryBuilder->buildColumnListQuery());

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
        if (is_null(self::$CONNECTION))
        {
            return new NullConnection(null);
        }

        return self::$CONNECTION;
    }

    public static function setConnection(Connection $connection)
    {
        self::$CONNECTION = $connection;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getAttribute($key)
    {
        $data = null;

        if (isset($this->attributes[$key]))
        {
            $data = $this->attributes[$key];
        }
        else if (method_exists($this, $key))
        {
            $data = $this->{$key}();
            $this->attributes[$key] = $data;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getReadOnlyAttributes()
    {
        return $this->readOnlyAttributes;
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
     * @return array
     */
    public function normalize()
    {
        return $this->attributes;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
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
        $this->setAttribute($key, $value);
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