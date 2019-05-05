<?php

namespace Intersect\Database\Model;

use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Query\ModelAliasFactory;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Model\Validation\Validation;

abstract class AssociativeModel extends AbstractModel implements Validation {

    private $columnOneName;
    private $columnTwoName;

    public function __construct()
    {
        parent::__construct();

        $this->columnOneName = $this->getColumnOneName();
        $this->columnTwoName = $this->getColumnTwoName();
    }

    abstract protected function getColumnOneName();
    abstract protected function getColumnTwoName();

    public function getValidatorMap()
    {
        return [
            $this->columnOneName => 'required',
            $this->columnTwoName => 'required'
        ];
    }

    /**
     * @param $columnOneValue
     * @param $columnTwoValue
     * @return static|null
     */
    public static function findAssociation($columnOneValue, $columnTwoValue)
    {
        /** @var AssociativeModel $modelClass */
        $modelClass = new static();
        $model = null;

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnOneName(), $columnOneValue);
        $queryParameters->equals($modelClass->getColumnTwoName(), $columnTwoValue);
        $queryParameters->setLimit(1);

        $models = self::find($queryParameters);

        if (count($models) == 1)
        {
            $model = $models[0];
        }

        return $model;
    }

    /**
     * @param $columnOneValue
     * @return static[]
     */
    public static function findAssociationsForColumnOne($columnOneValue)
    {
        /** @var AssociativeModel $modelClass */
        $modelClass = new static();
        $model = null;

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnOneName(), $columnOneValue);

        return self::find($queryParameters);
    }

    /**
     * @param $columnTwoValue
     * @return static[]
     */
    public static function findAssociationsForColumnTwo($columnTwoValue)
    {
        /** @var AssociativeModel $modelClass */
        $modelClass = new static();
        $model = null;

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnTwoName(), $columnTwoValue);

        return self::find($queryParameters);
    }

    /**
     * @param QueryParameters $queryParameters
     * @return static[]
     */
    private static function find(QueryParameters $queryParameters)
    {
        $modelClass = new static();
        $isColumnOverride = (!is_null($queryParameters) && count($queryParameters->getColumns()) > 0);

        if ($isColumnOverride)
        {
            $modelClass->columns = $queryParameters->getColumns();
        }

        $tableAlias = ModelAliasFactory::generateAlias($modelClass);
        $queryBuilder = $modelClass->getConnection()->getQueryBuilder();

        $result = $queryBuilder->select($modelClass->getColumnList(), $queryParameters)->table($modelClass->getTableName(), $modelClass->getPrimaryKey(), $tableAlias)->get();

        $models = [];

        foreach ($result->getRecords() as $record)
        {
            $models[] = self::newInstance($record);
        }

        return $models;
    }

    /**
     * @return bool
     * @throws DatabaseException
     * @throws \Intersect\Database\Exception\ValidationException
     */
    public function delete()
    {
        $this->validate();

        $queryParameters = new QueryParameters();
        $queryParameters->equals($this->getColumnOneName(), $this->getColumnOneValue());
        $queryParameters->equals($this->getColumnTwoName(), $this->getColumnTwoValue());
        $queryParameters->setLimit(1);

        $queryBuilder = $this->getConnection()->getQueryBuilder();
        $result = $queryBuilder->delete($queryParameters)->table($this->tableName, $this->getColumnOneName())->get();

        return ($result->getAffectedRows() == 1);
    }

    /**
     * @return static|null
     * @throws DatabaseException
     * @throws \Intersect\Database\Exception\ValidationException
     */
    public function save($forceSave = false)
    {
        if (!$this->isDirty() && !$forceSave)
        {
            return $this;
        }
        
        $this->validate();

        $queryBuilder = $this->getConnection()->getQueryBuilder();
        
        try {
            $queryBuilder->insert($this->attributes)->table($this->tableName, $this->primaryKey)->get();
        } catch (DatabaseException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') === false)
            {
                throw $e;
            }
        }

        return $this->findAssociation($this->getColumnOneValue(), $this->getColumnTwoValue());
    }

    private function getColumnOneValue()
    {
        return $this->getAttribute($this->columnOneName);
    }

    private function getColumnTwoValue()
    {
        return $this->getAttribute($this->columnTwoName);
    }

}