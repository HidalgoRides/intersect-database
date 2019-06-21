<?php

namespace Intersect\Database\Model;

use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Model\Validation\Validation;

abstract class AssociativeModel extends AbstractModel implements Validation {

    protected $updateOnDuplicateKey = true;

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

        $models = $modelClass->findInstances($queryParameters);

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
        
        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnOneName(), $columnOneValue);

        return $modelClass->findInstances($queryParameters);
    }

    /**
     * @param $columnTwoValue
     * @return static[]
     */
    public static function findAssociationsForColumnTwo($columnTwoValue)
    {
        /** @var AssociativeModel $modelClass */
        $modelClass = new static();

        $queryParameters = new QueryParameters();
        $queryParameters->equals($modelClass->getColumnTwoName(), $columnTwoValue);

        return $modelClass->findInstances($queryParameters);
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
        $result = $queryBuilder->delete($queryParameters)->table($this->tableName, $this->getColumnOneName())->schema($this->schema)->get();

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

        $columnOneName = $this->getColumnOneName();
        $columnTwoName = $this->getColumnTwoName();
        $columnOneValue = $this->getColumnOneValue();
        $columnTwoValue = $this->getColumnTwoValue();

        $queryBuilder = $this->getConnection()->getQueryBuilder();
        
        try {
            $queryBuilder->insert($this->attributes)->table($this->tableName, $this->primaryKey)->schema($this->schema)->get();
        } catch (DatabaseException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'duplicate key') === false)
            {
                throw $e;
            }
            else if ($this->updateOnDuplicateKey)
            {
                $updateAttributes = $this->attributes;
                unset($updateAttributes[$columnOneName]);
                unset($updateAttributes[$columnTwoName]);

                $queryBuilder = $this->getConnection()->getQueryBuilder();
                $queryBuilder->update($updateAttributes)
                    ->table($this->tableName)
                    ->schema($this->schema)
                    ->whereEquals($columnOneName, $columnOneValue)
                    ->whereEquals($columnTwoName, $columnTwoValue)
                    ->get();
            }
        }

        return $this->findAssociation($columnOneValue, $columnTwoValue);
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