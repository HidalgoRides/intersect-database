<?php

namespace Intersect\Database\Query\Builder;

use Closure;
use Intersect\Database\Query\Query;
use Intersect\Database\Query\Result;
use Intersect\Database\Schema\Key\Key;
use Intersect\Database\Schema\Blueprint;
use Intersect\Database\Schema\Key\Index;
use Intersect\Database\Schema\Key\UniqueKey;
use Intersect\Database\Connection\Connection;
use Intersect\Database\Query\QueryParameters;
use Intersect\Database\Schema\Key\ForeignKey;
use Intersect\Database\Schema\Key\PrimaryKey;
use Intersect\Database\Schema\ColumnBlueprint;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Query\Builder\Condition\InCondition;
use Intersect\Database\Query\Builder\Condition\LikeCondition;
use Intersect\Database\Query\Builder\Condition\NullCondition;
use Intersect\Database\Query\Builder\QueryConditionVisitable;
use Intersect\Database\Query\Builder\Condition\QueryCondition;
use Intersect\Database\Query\Builder\Condition\EqualsCondition;
use Intersect\Database\Query\Builder\Condition\BetweenCondition;
use Intersect\Database\Query\Builder\Condition\NotNullCondition;
use Intersect\Database\Schema\Resolver\ColumnDefinitionResolver;
use Intersect\Database\Query\Builder\Condition\NotEqualsCondition;
use Intersect\Database\Query\Builder\Condition\QueryConditionType;
use Intersect\Database\Query\Builder\Condition\QueryConditionGroup;
use Intersect\Database\Query\Builder\Condition\BetweenDatesCondition;

abstract class QueryBuilder {

    private static $ACTION_SELECT = 'select';
    private static $ACTION_SELECT_MAX = 'selectMax';
    private static $ACTION_COUNT = 'count';
    private static $ACTION_DELETE = 'delete';
    private static $ACTION_UPDATE = 'update';
    private static $ACTION_INSERT = 'insert';
    private static $ACTION_COLUMNS = 'columns';
    private static $ACTION_CREATE_TABLE = 'createTable';
    private static $ACTION_CREATE_TABLE_IF_NOT_EXISTS = 'createTableIfNotExists';
    private static $ACTION_DROP_TABLE = 'dropTable';
    private static $ACTION_DROP_TABLE_IF_EXISTS = 'dropTableIfExists';
    private static $ACTION_DROP_COLUMNS = 'dropColumns';
    private static $ACTION_ADD_COLUMN = 'addColumn';
    private static $ACTION_CREATE_INDEX = 'createIndex';
    private static $ACTION_DROP_INDEX = 'dropIndex';
    private static $ACTION_ADD_FOREIGN_KEY = 'addForeignKey';
    private static $ACTION_DROP_FOREIGN_KEY = 'dropForeignKey';

    protected $columnData = [];
    protected $columns = ['*'];
    protected $joinQueries = [];
    protected $schema;
    protected $tableName;

    protected $alias;
    protected $action;
    /** @var Blueprint */
    protected $blueprint;
    /** @var ColumnBlueprint */
    protected $columnBlueprint;
    /** @var Connection */
    protected $connection;
    protected $indexName;
    protected $limit;
    protected $order;
    protected $useAliases = false;
    protected $primaryKey = 'id';
    /** @var Key */
    protected $key;

    /** @var QueryCondition[] */
    protected $queryConditions = [];

    /** @var QueryParameters */
    protected $queryParameters;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->queryParameters = new QueryParameters();
    }

     abstract protected function buildSelectQuery();
     abstract protected function buildInsertQuery();
     abstract protected function buildUpdateQuery();
     abstract protected function buildDeleteQuery();
     abstract protected function buildCountQuery();
     abstract protected function buildColumnQuery();
     abstract protected function buildIndexDefinition(Index $index);
     abstract protected function buildForeignKeyDefinition(ForeignKey $foreignKey);
     abstract protected function buildPrimaryKeyDefinition(PrimaryKey $primaryKey);
     abstract protected function buildUniqueKeyDefinition(UniqueKey $uniqueKey);

     /** @return ColumnDefinitionResolver */
     abstract protected function getColumnDefinitionResolver();

    /**
     * @param $bypassCache
     * @return Result
     * @throws DatabaseException
     */
    public function get($bypassCache = false)
    {
        $query = $this->build();
        return (!is_null($query) ? $this->connection->run($query, $bypassCache) : new Result());
    }

    /** @return QueryBuilder */
    public function createTable(Blueprint $blueprint)
    {
        $this->action = self::$ACTION_CREATE_TABLE;
        $this->blueprint = $blueprint;
        return $this;
    }

    /** @return QueryBuilder */
    public function createTableIfNotExists(Blueprint $blueprint)
    {
        $this->action = self::$ACTION_CREATE_TABLE_IF_NOT_EXISTS;
        $this->blueprint = $blueprint;
        return $this;
    }

    /** @return QueryBuilder */
    public function dropTable($tableName)
    {
        $this->action = self::$ACTION_DROP_TABLE;
        $this->tableName = $tableName;
        return $this;
    }

    /** @return QueryBuilder */
    public function dropTableIfExists($tableName)
    {
        $this->action = self::$ACTION_DROP_TABLE_IF_EXISTS;
        $this->tableName = $tableName;
        return $this;
    }

    public function dropColumns(array $columns)
    {
        $this->action = self::$ACTION_DROP_COLUMNS;
        $this->columns = $columns;
        return $this;
    }

    public function addColumn(ColumnBlueprint $columnBlueprint)
    {
        $this->action = self::$ACTION_ADD_COLUMN;
        $this->columnBlueprint = $columnBlueprint;
        return $this;
    }

    public function createIndex(array $columns, $indexName)
    {
        $this->action = self::$ACTION_CREATE_INDEX;
        $this->columns = $columns;
        $this->indexName = $indexName;
        return $this;
    }

    public function dropIndex($indexName)
    {
        $this->action = self::$ACTION_DROP_INDEX;
        $this->indexName = $indexName;
        return $this;
    }

    public function addForeignKey($fromColumn, $toColumn, $onTable, $onTableSchema = 'public', $keyName = null)
    {
        $onTableSchema = (!is_null($onTableSchema) ? $onTableSchema : 'public');
        $keyName = (!is_null($keyName) ? $keyName : $fromColumn . '_' . $onTable . '_' . $toColumn);
        $this->key = new ForeignKey($keyName, $fromColumn, $toColumn, $onTable, $onTableSchema);

        $this->action = self::$ACTION_ADD_FOREIGN_KEY;
        return $this;
    }

    public function dropForeignKey($keyName)
    {
        $this->action = self::$ACTION_DROP_FOREIGN_KEY;
        $this->key = new Key($this->tableName, [], $keyName);
        return $this;
    }

    /** @return QueryBuilder */
    public function select(array $columns = [], QueryParameters $queryParameters = null)
    {
        $this->action = self::$ACTION_SELECT;
        $this->useAliases = true;

        if (count($columns) > 0)
        {
            $this->columns = $columns;
        }

        if (!is_null($queryParameters))
        {
            $this->initFromQueryParameters($queryParameters);
        }

        return $this;
    }

    public function selectMax($column, QueryParameters $queryParameters = null)
    {
        $this->action = self::$ACTION_SELECT_MAX;

        $this->columns = [$column];

        if (!is_null($queryParameters))
        {
            $this->initFromQueryParameters($queryParameters);
        }

        return $this;
    }

    /** @return QueryBuilder */
    public function count(QueryParameters $queryParameters = null)
    {
        $this->action = self::$ACTION_COUNT;
        
        if (!is_null($queryParameters))
        {
            $this->initFromQueryParameters($queryParameters);
        }

        return $this;
    }

    /** @return QueryBuilder */
    public function delete(QueryParameters $queryParameters = null)
    {
        $this->action = self::$ACTION_DELETE;

        if (!is_null($queryParameters))
        {
            $this->initFromQueryParameters($queryParameters);
        }

        return $this;
    }

    /** @return QueryBuilder */
    public function update(array $columnData, QueryParameters $queryParameters = null)
    {
        $this->action = self::$ACTION_UPDATE;
        $this->columnData = $columnData;

        if (!is_null($queryParameters))
        {
            $this->initFromQueryParameters($queryParameters);
        }

        return $this;
    }

    /** @return QueryBuilder */
    public function insert(array $columnData)
    {
        $this->action = self::$ACTION_INSERT;
        $this->columnData = $columnData;
        return $this;
    }

    /** @return QueryBuilder */
    public function columns()
    {
        $this->action = self::$ACTION_COLUMNS;
        return $this;
    }

    private function initFromQueryParameters(QueryParameters $queryParameters)
    {
        $this->queryParameters = $queryParameters;
        $this->addQueryConditions($queryParameters->getQueryConditions());

        $order = $queryParameters->getOrder();
        if (!is_null($order))
        {
            $orderParts = explode(' ', $queryParameters->getOrder());
            $orderColumn = $orderParts[0];
            $orderDirection = (isset($orderParts[1]) ? $orderParts[1] : 'asc');
            $this->orderBy($orderColumn, $orderDirection);
        }

        $limit = $queryParameters->getLimit();
        if (!is_null($limit))
        {
            $this->limit($limit);
        }
    }

    /** @return QueryBuilder */
    public function table($tableName, $primaryKey = 'id', $alias = null)
    {
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
        $this->alias = $alias;
        return $this;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getLimit()
    {
        return (int) $this->limit;
    }

    /** @return QueryBuilder */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /** @return QueryBuilder */
    public function orderBy($column, $direction = 'asc')
    {
        $this->order = [
            $column,
            strtolower($direction)
        ];
        return $this;
    }

    /** @return QueryBuilder */
    public function schema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    /** @return QueryBuilder */
    public function addQueryCondition(QueryConditionVisitable $queryCondition)
    {
        $this->queryConditions[] = $queryCondition;
        return $this;
    }

    /** @return QueryBuilder */
    public function addQueryConditions(array $queryConditions)
    {
        foreach ($queryConditions as $queryCondition)
        {
            $this->addQueryCondition($queryCondition);
        }
        return $this;
    }

    public function getQueryConditions()
    {
        return $this->queryConditions;
    }

    public function group(Closure $closure)
    {
        $this->addGroupConditions($closure, QueryConditionType::AND);
        return $this;
    }

    public function groupOr(Closure $closure)
    {
        $this->addGroupConditions($closure, QueryConditionType::OR);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereEquals($column, $value)
    {
        $this->queryConditions[] = new EqualsCondition($column, $value);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereNull($column)
    {
        $this->queryConditions[] = new NullCondition($column);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereNotNull($column)
    {
        $this->queryConditions[] = new NotNullCondition($column);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereNotEquals($column, $value)
    {
        $this->queryConditions[] = new NotEqualsCondition($column, $value);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereLike($column, $value)
    {
        $this->queryConditions[] = new LikeCondition($column, $value);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereIn($column, $values, $quoteValues = false)
    {
        if (!is_array($values))
        {
            $values = [$values];
        }

        if ($quoteValues)
        {
            foreach ($values as &$value)
            {
                $value = "'" . $value . "'";
            }
        }

        $this->queryConditions[] = new InCondition($column, $values);
        return $this;
    }

    /** @return QueryBuilder */
    public function whereBetween($column, $startValue, $endValue)
    {
        $this->queryConditions[] = new BetweenCondition($column, $startValue, $endValue);
        return $this;
    }
    
    /** @return QueryBuilder */
    public function whereBetweenDates($column, $startDate, $endDate)
    {
        $this->queryConditions[] = new BetweenDatesCondition($column, $startDate, $endDate);
        return $this;
    }
    
    /** @return QueryBuilder */
    public function joinLeft($joinTableName, $joinColumnName, $originalColumnName, array $joinColumns = [], $joinTableAlias = null)
    {
        $originalTableAlias = $this->getTableAlias();
        $joinTableAlias = ($this->useAliases && !is_null($joinTableAlias)) ? $joinTableAlias : $joinTableName;
        $queryString = 'left join ' . $this->buildTableNameWithAlias($joinTableName, $joinTableAlias) . ' on ' . $originalTableAlias . '.' . $originalColumnName . ' = ' . $joinTableAlias . '.' . $joinColumnName;

        $allNamedColumns = [];
        $this->addNamedColumns($allNamedColumns, $joinColumns, $joinTableAlias);
        
        $this->joinQueries[] = [
            'columns' => $allNamedColumns,
            'queryString' => $queryString,
            'bindParameters' => []
        ];
        return $this;
    }

    /** @return Query */
    public function build()
    {
        $query = new Query();

        switch ($this->action)
        {
            case self::$ACTION_SELECT:
                $query = $this->buildSelectQuery();
                break;
            case self::$ACTION_SELECT_MAX:
                $query = $this->buildSelectMaxQuery();
                break;
            case self::$ACTION_INSERT:
                $query = $this->buildInsertQuery();
                break;
            case self::$ACTION_UPDATE:
                $query = $this->buildUpdateQuery();
                break;
            case self::$ACTION_DELETE:
                $query = $this->buildDeleteQuery();
                break;
            case self::$ACTION_COUNT:
                $query = $this->buildCountQuery();
                break;
            case self::$ACTION_COLUMNS:
                $query = $this->buildColumnQuery();
                break;
            case self::$ACTION_CREATE_TABLE:
                $query = $this->buildCreateTableQuery($this->blueprint);
                break;
            case self::$ACTION_CREATE_TABLE_IF_NOT_EXISTS:
                $query = $this->buildCreateTableQuery($this->blueprint, true);
                break;
            case self::$ACTION_DROP_TABLE:
                $query = $this->buildDropTableQuery();
                break;
            case self::$ACTION_DROP_TABLE_IF_EXISTS:
                $query = $this->buildDropTableIfExistsQuery();
                break;
            case self::$ACTION_DROP_COLUMNS:
                $query = $this->buildDropColumnsQuery();
                break;
            case self::$ACTION_ADD_COLUMN:
                $query = $this->buildAddColumnQuery();
                break;
            case self::$ACTION_DROP_INDEX:
                $query = $this->buildDropIndexQuery();
                break;
            case self::$ACTION_CREATE_INDEX:
                $query = $this->buildCreateIndexQuery();
                break;
            case self::$ACTION_ADD_FOREIGN_KEY:
                $query = $this->buildAddForeignKeyQuery();
                break;
            case self::$ACTION_DROP_FOREIGN_KEY:
                $query = $this->buildDropForeignKeyQuery();
                break;
        }

        return $query;
    }

    protected function getTableAlias()
    {
        return ($this->useAliases) ? $this->alias : null;
    }

    protected function addNamedColumns(&$allColumns, $columnList, $alias)
    {
        foreach ($columnList as $column)
        {
            $allColumns[] = $this->buildColumnWithAlias($column, $alias);
        }
    }

    protected function appendWhereConditions(Query $query)
    {
        $queryConditions = $this->queryConditions;

        if (count($queryConditions) > 0)
        {
            $conditionQuery = new Query();
            $rootConjunction = $this->queryParameters->getRootConjunction();
            $alias = $this->getTableAlias();
            $visitor = new QueryConditionVisitor($conditionQuery, $alias);

            for ($i = 0; $i < count($queryConditions); $i++)
            {
                $queryCondition = $queryConditions[$i];

                if ($queryCondition instanceof QueryConditionVisitable)
                {
                    if ($i > 0)
                    {
                        $conditionQuery->appendSql(' ' . $rootConjunction . ' ');
                    }

                    $queryCondition->accept($visitor);
                }
            }

            $query->appendSql(' where ' . preg_replace("/^\s+" . $rootConjunction . "+\s|\s+" . $rootConjunction . "+\s$/","", $conditionQuery->getSql()));
            
            foreach ($conditionQuery->getBindParameters() as $key => $value)
            {
                $query->bindParameter($key, $value);
            }
        }
    }

    protected function appendOptions(Query $query)
    {
        $alias = $this->getTableAlias();
        
        if (!is_null($this->order))
        {
            $query->appendSql(' order by ' . ((!is_null($alias)) ? $alias . '.' : '') . $this->order[0] . ' ' . $this->order[1]);
        }

        if (!is_null($this->limit))
        {
            $query->appendSql(' limit ' . $this->limit);
        }
    }

    protected function buildFinalQuery($sql, array $bindParameters = [], $appendWhereConditions = true, $appendOptions = true)
    {
        $query = new Query($sql, $bindParameters);

        if ($appendWhereConditions)
        {
            $this->appendWhereConditions($query);
        }

        if ($appendOptions)
        {
            $this->appendOptions($query);
        }

        $sql = rtrim($query->getSql(), ';');
        if ($sql != null)
        {
            $query->appendSql(';');
        }

        return $query;
    }

    protected function buildSelectMaxQuery()
    {
        $queryString = 'select max(' . $this->columns[0] . ') as max_value from ' . $this->wrapTableName($this->tableName);

        return $this->buildFinalQuery($queryString);
    }

    protected function buildCreateTableQuery(Blueprint $blueprint, $suffixWithIfNotExists = false)
    {
        $columnDefinitionStrings = [];
        $columnDefinitionResolver = $this->getColumnDefinitionResolver();

        foreach ($blueprint->getColumnDefinitions() as $columnDefinition)
        {
            if (!is_null($columnDefinition))
            {
                $columnDefinitionStrings[] = $columnDefinitionResolver->resolve($columnDefinition);
            }
        }

        $keyDefinitionStrings = [];
        foreach ($blueprint->getKeys() as $key)
        {
            $definition = null;

            if ($key instanceof UniqueKey)
            {
                $definition = $this->buildUniqueKeyDefinition($key);
            }
            else if ($key instanceof PrimaryKey)
            {
                $definition = $this->buildPrimaryKeyDefinition($key);
            }
            else if ($key instanceof ForeignKey)
            {
                $definition = $this->buildForeignKeyDefinition($key);
            }
            else if ($key instanceof Index)
            {
                $definition = $this->buildIndexDefinition($key);
            }

            if (!is_null($definition))
            {
                $keyDefinitionStrings[] = $definition;
            }
        }

        $ifNotExists = (($suffixWithIfNotExists) ? 'if not exists ' : '');
        $queryString = 'create table ' . $ifNotExists . $this->wrapTableName($blueprint->getTableName()) . ' (';
        $queryString .= implode(', ', $columnDefinitionStrings);

        if (count($keyDefinitionStrings) > 0)
        {
            $queryString .= ', ' . implode(', ', $keyDefinitionStrings);
        }

        $queryString .= ')';

        $tableOptions = $this->buildCreateTableOptions();

        if (!is_null($tableOptions))
        {
            $queryString .= ' ' . $tableOptions;
        }

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildCreateTableOptions() {}

    protected function buildDropTableQuery()
    {
        $queryString = 'drop table ' . $this->wrapTableName($this->tableName);

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildDropTableIfExistsQuery()
    {
        $queryString = 'drop table if exists ' . $this->wrapTableName($this->tableName);

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildAddColumnQuery()
    {
        $columnDefinitionResolver = $this->getColumnDefinitionResolver();

        $queryString = null;
        $columnDefinition = $this->columnBlueprint->getColumnDefinition();

        if (!is_null($columnDefinition))
        {
            $queryString = 'alter table ' . $this->wrapTableName($this->tableName) . ' add column ' . $columnDefinitionResolver->resolve($columnDefinition);
        }

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildDropColumnsQuery()
    {
        $dropColumnStrings = [];

        foreach ($this->columns as $column)
        {
            $dropColumnStrings[] = 'drop column ' . $this->wrapColumnName($column);
        }

        $queryString = 'alter table ' . $this->wrapTableName($this->tableName) . ' ' . implode(', ', $dropColumnStrings);

        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildCreateIndexQuery()
    {
        $queryString = 'create index ' . $this->indexName . ' on ' . $this->tableName . ' (' . implode(', ', $this->columns) . ')';
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildDropIndexQuery()
    {
        $queryString = 'drop index ' . $this->indexName;
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildAddForeignKeyQuery()
    {
        $queryString = 'alter table ' . $this->wrapTableName($this->tableName) . ' add ' . $this->buildForeignKeyDefinition($this->key);
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildDropForeignKeyQuery()
    {
        $queryString = 'alter table ' . $this->wrapTableName($this->tableName) . ' drop foreign key ' . $this->key->getName();
        
        return $this->buildFinalQuery($queryString, [], false, false);
    }

    protected function buildColumnWithAlias($column, $alias = null)
    {
        return (!is_null($alias)) ? ($alias . '.' . $column . " as '" . $alias . '.' . $column . "'") : $column;
    }

    protected function buildPlaceholderWithAlias($key, $alias = null)
    {
        return (!is_null($alias)) ? ($alias . '_' . $key) : $key;
    }

    protected function buildTableNameWithAlias($tableName, $alias = null)
    {
        $tableName = $this->wrapTableName($tableName);

        return (!is_null($alias)) ? ($tableName . ' as ' . $alias) : $tableName;
    }

    protected function wrapTableName($tableName)
    {
        return '`' . $tableName . '`';
    }

    protected function wrapColumnName($columnName)
    {
        return '`' . $columnName . '`';
    }

    private function addGroupConditions(Closure $closure, $type)
    {
        $queryBuilder = new static($this->connection);
        $queryConditionGroup = new QueryConditionGroup($type);
        $closure($queryBuilder);

        $queryConditionGroup->addConditions($queryBuilder->getQueryConditions());
        $this->queryConditions[] = $queryConditionGroup;
    }

}