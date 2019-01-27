<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Model\Model;
use Intersect\Database\Query\Query;
use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Model\AbstractModel;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Query\QueryRelationship;
use Intersect\Database\Model\Relationship\Relational;
use Intersect\Database\Model\Relationship\EagerRelationship;

class ModelQueryBuilder extends DefaultQueryBuilder {

    /** @var AbstractModel */
    private $model;

    public function __construct(AbstractModel $model, QueryParameters $queryParameters = null)
    {
        parent::__construct($model->getTableName(), $queryParameters);

        $this->model = $model;

        return $this;
    }

    /**
     * @param array $columns
     * @return Query
     * @throws \Intersect\Database\Exception\DatabaseException
     */
    public function buildSelectQuery(array $columns = [])
    {
        $query = new Query();
        $joinSql = '';
        $model = $this->model;
        $allColumns = [];

        $modelAlias = AliasFactory::getAlias($model->getTableName());

        $this->addNamedColumns($allColumns, $columns, $modelAlias);

        $pullAllColumns = false;
        if (count($allColumns) == 0)
        {
            $allColumns[] = $modelAlias . '.*';
            $pullAllColumns = true;
        }

        if ($model instanceof Model && $model instanceof Relational && !$this->queryParameters->getBypassEagerLoading())
        {
            $eagerRelationships = [];

            /** @var EagerRelationship $relationship */
            foreach ($model->getEagerRelationshipMap() as $relationship)
            {
                $eagerRelationships[$relationship->getColumn()] = $relationship;
            }

            if (count($eagerRelationships) > 0)
            {
                /** @var Model $relationshipClass */
                foreach ($eagerRelationships as $relationship)
                {
                    if (in_array($relationship->getColumn(), $columns) || $pullAllColumns)
                    {
                        $relationshipClassName = $relationship->getModelClass();
                        $relationshipClass = new $relationshipClassName;
                        $relationshipAttribute = $relationship->getAttribute();
                        $relationshipAlias = AliasFactory::getAlias($relationshipAttribute);

                        $this->addNamedColumns($allColumns, $relationshipClass->getColumnList(), $relationshipAlias);

                        $joinSql .= ' LEFT JOIN ' . $relationshipClass->getTableName() . ' as ' . $relationshipAlias . ' ON ' . $modelAlias . '.' . $relationship->getColumn() . ' = ' . $relationshipAlias . '.' . $relationshipClass->getPrimaryKey();

                        $query->addRelationship(new QueryRelationship($relationshipAlias, $relationshipClassName, $relationshipAttribute));
                    }
                }
            }
        }

        $sql = 'SELECT ' . implode(', ', $allColumns) . ' FROM ' . $this->wrapValue($this->tableName) . ' as ' . $modelAlias;

        $query->setSql($sql . $joinSql);

        $this->appendWhereConditions($query, true);
        $this->appendQueryOptions($query);

        return $query;
    }

}