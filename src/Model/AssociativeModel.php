<?php

namespace Intersect\Database\Model;

use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Model\Validation\Validation;
use Intersect\Database\Query\Builder\DefaultQueryBuilder;
use Intersect\Database\Query\Builder\ModelQueryBuilder;
use Intersect\Database\Query\QueryParameters;

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
     * @return mixed|null
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
     * @return array
     */
    public static function findAssociationsForColumnOne($columnOneValue)
    {
        /** @var AssociativeModel $modelClass */
        $modelClass = new static();
        $model = null;

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnOneName(), $columnOneValue);

        $models = self::find($queryParameters);

        return $models;
    }

    /**
     * @param $columnTwoValue
     * @return array
     */
    public static function findAssociationsForColumnTwo($columnTwoValue)
    {
        /** @var AssociativeModel $modelClass */
        $modelClass = new static();
        $model = null;

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnTwoName(), $columnTwoValue);

        $models = self::find($queryParameters);

        return $models;
    }

    /**
     * @param QueryParameters $queryParameters
     * @return array
     */
    private static function find(QueryParameters $queryParameters)
    {
        $modelClass = new static();
        $isColumnOverride = (!is_null($queryParameters) && count($queryParameters->getColumns()) > 0);

        if ($isColumnOverride)
        {
            $modelClass->columns = $queryParameters->getColumns();
        }

        $models = [];
        $queryBuilder = new DefaultQueryBuilder($modelClass->getTableName(), $queryParameters);

        $query = $queryBuilder->buildSelectQuery($modelClass->getColumnList());

        $result = $modelClass->getConnection()->run($query);

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

        $queryBuilder = new ModelQueryBuilder($this, $queryParameters);
        $query = $queryBuilder->buildDeleteQuery();

        $result = $this->getConnection()->run($query);

        return ($result->getAffectedRows() == 1);
    }

    /**
     * @return mixed|null
     * @throws DatabaseException
     * @throws \Intersect\Database\Exception\ValidationException
     */
    public function save()
    {
        $this->validate();

        $queryBuilder = new ModelQueryBuilder($this);

        $query = $queryBuilder->buildInsertQuery($this->attributes);
        $result = null;

        try {
            $this->getConnection()->run($query);
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