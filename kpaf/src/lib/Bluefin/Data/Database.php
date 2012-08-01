<?php

namespace Bluefin\Data;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Data\Db\DbInterface;

class Database
{
    const KW_SQL_ROWS_PER_PAGE = 'rows';
    const KW_SQL_PAGE_INDEX = 'page';
    const KW_SQL_TOTAL_ROWS = 'total';
    const KW_SQL_TOTAL_PAGES = 'pages';
    const DEFAULT_ROWS_PER_PAGE = 30;

    private $_namespace;
    private $_name;

    /**
     * @var array
     */
    private $_modelClasses;

    /**
     * @var \Bluefin\Data\Db\DbInterface
     */
    private $_adapter;

    protected function __construct($namespace, $name, $modelClasses, DbInterface $adapter)
    {
        $this->_namespace = $namespace;
        $this->_name = $name;
        $this->_modelClasses = $modelClasses;

        $this->_adapter = $adapter;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return Db\DbInterface
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    public function getModelClass($modelName)
    {
        \Bluefin\App::assert(array_key_exists($modelName, $this->_modelClasses), "Model [{$modelName}] not found!");
        return $this->_modelClasses[$modelName];
    }

    /**
     * @param $sql
     * @param array $bind
     * @param null $fetchMode
     * @return mixed
     */
    public function fetchAll($sql, $bind = array(), $fetchMode = null)
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchAll($fetchMode);
        return $result;
    }

    /**
     * @param $sql
     * @param array $bind
     * @param null $fetchMode
     * @return mixed
     */
    public function fetchRow($sql, $bind = array(), $fetchMode = null)
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetch($fetchMode);
        return $result;
    }

    /**
     * Fetches the first column of the first row of the SQL result.
     *
     * @param string|Zend_Db_Select $sql An SQL SELECT statement.
     * @param mixed $bind Data to bind into SELECT placeholders.
     * @return string
     */
    public function fetchOne($sql, $bind = array())
    {
        $stmt = $this->query($sql, $bind);
        $result = $stmt->fetchColumn(0);
        return $result;
    }

    public function insert(ModelMetadata $schema, array $bind)
    {
        // extract and quote col names from the array keys
        $cols = array();
        $values = $this->_extractBoundParams($schema, $bind, $cols);

        // build the statement
        $sql = $this->_adapter->buildInsertSQL($schema->getModelName(), $cols, $values);

        $stmt = $this->query($sql, $bind);
        return $stmt->rowCount();
    }

    public function update(ModelMetadata $schema, array $bind, $condition = null)
    {
        $values = $this->_extractBoundParams($schema, $bind);

        $relations = new Relations($schema);
        $where = $this->_normalizeCondition($relations, $condition);

        $bind = array_merge($bind, $condition);

        $sql = $this->_adapter->buildUpdateSQL($relations, $values, $where);

        $stmt = $this->query($sql, $bind);
        return $stmt->rowCount();
    }

    public function delete(ModelMetadata $schema, $condition, $physical = false)
    {
        if ($schema->hasFeature(Convention::FEATURE_LOGICAL_DELETION) && !$physical)
        {
            return self::update($schema, array('is_deleted' => 1), $condition);
        }

        $relations = new Relations($schema);
        $where = $this->_normalizeCondition($relations, $condition);

        $sql = $this->_adapter->buildDeleteSQL($relations, $where);

        $stmt = $this->query($sql, $condition);
        return $stmt->rowCount();
    }

    /**
     * @param $sql
     * @param array $dbParams
     * @return \PDOStatement|\Zend_Db_Statement
     */
    public function query($sql, array $dbParams = array())
    {
        // prepare and execute the statement with profiling
        $stmt = $this->_dao->prepare($sql);
        /**
         * @var \PDOStatement $pdoStmt
         */
        $pdoStmt = $stmt->getDriverStatement();

        $pos = 1;
        foreach ($dbParams as $dbParam)
        {
            //var_dump($dbParam); echo "<br>";
            /**
             * @var \Bluefin\Data\DbParam $dbParam
             */
            $pdoStmt->bindValue($pos++, $dbParam->value, $dbParam->dbType);
        }

        try
        {
            $pdoStmt->execute();
        }
        catch (\PDOException $e)
        {
            if (23000 == $e->getCode())
            {
                $data = array();
                foreach ($dbParams as $dbParam)
                {
                    $data[] = $dbParam->value;
                }
                $data = implode(',', $data);

                App::getInstance()->log()->err("Error executing SQL: {$sql}, with data: {$data}");
            }

            throw $e;
        }

        // return the results embedded in the prepared statement object
        $stmt->setFetchMode($this->_dao->getFetchMode());
        return $stmt;
    }

    /**
     * @param array $selected
     * @param $from
     * @param null $where
     * @param array|null $grouping
     * @param array|null $ranking
     * @param array|null $pagination
     * @return string
     */
    public function buildSelectSQL($selected, $from, &$where = null, array $grouping = null, array $ranking = null, array $pagination = null)
    {
        $select = $this->_buildSelect($selected, $from, $where, $grouping, $ranking);
        return $this->_adapter->buildSelectSQL($select, $pagination);
    }

    public function buildSelectSQLWithCount(array $selected, $from, &$where = null, array $grouping = null, array $ranking = null, array &$pagination = null)
    {        
        $select = $this->_buildSelect($selected, $from, $where, $grouping, $ranking);
        $sql2 = $this->_adapter->buildSelectSQL($select, $pagination);

        $pkColumn = $select->getRelations()->getTableMetadata()->getPrimaryKey();
        $pkColumn = $this->_quoteColumn($select->getRelations(), null, $pkColumn);

        $select->setSelect("COUNT({$pkColumn})");
        $select->setOrderBy(null);

        return array($this->_adapter->buildSelectSQL($select), $sql2);
    }

    protected  function _buildSelect($selected, $from, &$where = null, array $grouping = null, array $ranking = null)
    {
        /**
         * @var \Bluefin\Data\Model $modelClass
         */
        $modelClass = $this->getModelClass($from);

        $relations = new Relations($modelClass::s_metadata());

        $selectOutput = array();
        $relations->splitRelationships($selected, $selectOutput);

        $select = new Select($relations);
        $select->setSelect($this->_normalizeSelectedColumns($relations, $selectOutput));

        if (isset($where))
        {
            $terms = $this->_normalizeCondition($relations, $where);
            $select->setWhere($this->_adapter->buildWhereClause($terms));
        }

        $select->setJoin($this->_adapter->buildJoinRelations($relations));

        isset($grouping) && $select->setGroupBy($this->_adapter->buildGroupByClause($grouping));
        isset($ranking) && $select->setOrderBy($this->_adapter->buildOrderByClause($ranking));

        return $select;
    }

    protected function _normalizeCondition(Relations $relations, &$condition)
    {
        if (empty($condition))
        {
            return '';
        }

        $output = array();
        $relations->splitRelationships($condition, $output, true);

        $terms = array();
        $values = array();

        // AND
        foreach ($output as $column => $term)
        {
            // is $column an int? (i.e. a formed condition)
            if (is_int($column))
            {
                // $term is the full condition
                if ($term instanceof DbCondition)
                {
                    /**
                     * @var \Bluefin\Data\DbCondition $term
                     */
                    $term = $term->__toString();
                }

                $terms[] = '(' . $term . ')';
                continue;
            }

            $tablePath = $this->_extractTablePathFromColumnName($column);

            if ($term instanceof \Zend_Db_Expr)
            {
                /**
                 * @var \Zend_Db_Expr $term
                 */
                $term = $term->__toString();
            }
            else if (isset($term))
            {
                $fieldOption = $relations->getFieldOptions($tablePath, $column);
                Type::convertValue($term, $fieldOption);
                $values[] = new DbParam($column, $term, $fieldOption);
                $term = '?';
            }

            $column = $this->_quoteColumn($relations, $tablePath, $column);
            $terms[] = $this->_adapter->combineCondition($column, $term);
        }

        $condition = $values;

        return $terms;
    }

    protected function _extractTablePathFromColumnName(&$columnName)
    {
        $pos = mb_strrpos($columnName, '.');

        if (false === $pos)
        {
            return null;
        }

        $table = mb_substr($columnName, 0, $pos);

        $columnName = mb_substr($columnName, $pos+1);

        return $table;
    }

    /**
     * @param Relations $relations
     * @param $tablePath
     * @param $columnName
     * @return string|void
     */
    protected function _quoteColumn(Relations $relations, $tablePath, $columnName)
    {
        if ($relations->hasAnyRelations())
        {
            $columnName = $this->_adapter->combineTableAndColumn($relations->getTableAlias($tablePath), $columnName);
        }
        else
        {
            App::assert(!isset($tablePath));
            $columnName = $this->_adapter->quoteIdentifier($columnName);
        }

        return $columnName;
    }

    protected function _normalizeSelectedColumns(Relations $relations, array $selected)
    {
        $output = array();

        foreach ($selected as $columnName => $alias)
        {
            //echo $columnName . "<br>";
            $tablePath = $this->_extractTablePathFromColumnName($columnName);
            $fieldOptions = $relations->getFieldOptions($tablePath, $columnName);

            $columnName = $this->_quoteColumn($relations, $tablePath, $columnName);

            $wrapped = $this->getAdapter()->wrapColumnOnSelect($fieldOptions[Type::FILTER_TYPE], $columnName);

            if (!isset($alias) && $wrapped != $columnName)
            {
                $alias = $columnName;
                $columnName = $wrapped;
            }

            $output[] = isset($alias) ? $this->_adapter->combineColumnAndAlias($columnName, $alias) : $columnName;
        }

        return implode(',', $output);
    }

    protected function _extractBoundParams(ModelMetadata $schema, array &$bind, array &$cols = null)
    {
        $values = array();
        $bindValues = array();

        foreach ($bind as $col => $val)
        {
            if (isset($cols))
            {
                $cols[] = $this->_adapter->quoteIdentifier($col);
            }

            if ($val instanceof \Zend_Db_Expr)
            {
                $val = $val->__toString();
            }
            else
            {
                $fieldOption = $schema->getFilterOption($col);

                $dbParam = new DbParam($col, $val, $fieldOption);
                Type::validateValue($dbParam->value, $fieldOption);
                $bindValues[] = $dbParam;

                $val = '?';
            }

            if (isset($cols))
            {
                $values[] = $val;
            }
            else
            {
                $values[] = $this->_adapter->quoteIdentifier($col) . '=' . $val;
            }
        }

        $bind = $bindValues;

        return $values;
    }
}