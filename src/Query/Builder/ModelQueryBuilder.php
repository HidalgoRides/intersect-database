<?php

namespace Intersect\Database\Query\Builder;

use Intersect\Database\Model\Model;
use Intersect\Database\Query\Query;
use Intersect\Database\Query\AliasFactory;
use Intersect\Database\Model\AbstractModel;
use Intersect\Database\Query\QueryParameters;

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

        $sql = 'SELECT ' . implode(', ', $allColumns) . ' FROM ' . $this->wrapValue($this->tableName) . ' as ' . $modelAlias;

        $query->setSql($sql . $joinSql);

        $this->appendWhereConditions($query, true);
        $this->appendQueryOptions($query);

        return $query;
    }

}