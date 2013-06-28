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

    const KW_SQL_CONDITION_LATEST = '*LATEST*';
    const KW_SQL_CONDITION_OUTPUT = '*SELECT*';
    const KW_SQL_CONDITION_PAGING = '*PAGING*';
    const KW_SQL_CONDITION_ORDER_BY = '*ORDER*';

    const KW_ALL_STATES = '_all';

    const FETCH_ROW_TO_ASSOC = \PDO::FETCH_ASSOC;
    const FETCH_ROW_TO_ARRAY = \PDO::FETCH_NUM;

    const FETCH_ALL_TO_ASSOC = \PDO::FETCH_ASSOC;
    const FETCH_ALL_TO_ARRAY = \PDO::FETCH_NUM;
    const FETCH_ALL_TO_KEY_PAIR = \PDO::FETCH_KEY_PAIR;

    const FETCH_COLUMN = \PDO::FETCH_COLUMN;
    const FETCH_COLUMN_UNIQUE = \PDO::FETCH_UNIQUE;
    const FETCH_GROUP = \PDO::FETCH_GROUP;

    public static function extractQueryCondition(&$condition, array &$outputColumns = null, array &$paging = null, array &$orderBy = null)
    {
        if (empty($condition)) return;

        if (!isset($outputColumns))
        {
            if (array_key_exists(Database::KW_SQL_CONDITION_OUTPUT, $condition))
            {
                $outputColumns = $condition[Database::KW_SQL_CONDITION_OUTPUT];
                unset($condition[Database::KW_SQL_CONDITION_OUTPUT]);
            }
            else
            {
                $outputColumns = ['*'];
            }
        }

        if (!isset($paging) && array_key_exists(Database::KW_SQL_CONDITION_PAGING, $condition))
        {
            $paging = $condition[Database::KW_SQL_CONDITION_PAGING];
            unset($condition[Database::KW_SQL_CONDITION_PAGING]);
        }

        if (array_key_exists(Database::KW_SQL_CONDITION_ORDER_BY, $condition))
        {
            $orderBy = $condition[Database::KW_SQL_CONDITION_ORDER_BY];
            unset($condition[Database::KW_SQL_CONDITION_ORDER_BY]);
        }
    }

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
        if (!array_key_exists($modelName, $this->_modelClasses))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Table '{$modelName}'' not found in db '{$this->_name}'!");
        }

        return $this->_modelClasses[$modelName];
    }

    public function getModel($modelName, $condition = null)
    {
        $modelClass = $this->getModelClass($modelName);
        return new $modelClass($condition);
    }

    public function insert(ModelMetadata $schema, array $bind, $onDuplicateUpdate = null)
    {
        // extract and quote col names from the array keys
        $cols = array();
        $values = $this->_prepareInsertParams($schema, $bind, $cols);

        if (!empty($onDuplicateUpdate))
        {
            $updateValues = $this->_prepareUpdateParams($schema, $onDuplicateUpdate);
            $bind = array_merge($bind, $onDuplicateUpdate);

            //[+]DEBUG
            if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
            {
                App::getInstance()->log()->debug($this->_dumpCondition($bind), \Bluefin\Log::CHANNEL_DIAG);
            }
            //[-]DEBUG

            $sql = $this->_adapter->buildInsertSQL($schema->getModelName(), $cols, $values, $updateValues);
        }
        else
        {
            //[+]DEBUG
            if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
            {
                App::getInstance()->log()->debug($this->_dumpCondition($bind), \Bluefin\Log::CHANNEL_DIAG);
            }
            //[-]DEBUG
            $sql = $this->_adapter->buildInsertSQL($schema->getModelName(), $cols, $values);
        }

        return $this->_adapter->query($sql, $bind);
    }

    public function update(ModelMetadata $schema, array $bind, $condition = null)
    {
        $values = $this->_prepareUpdateParams($schema, $bind);

        $relations = new Relations($schema);
        $where = $this->_normalizeCondition($relations, $condition);
        $bind = array_merge($bind, $condition);
        //[+]DEBUG
        if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
        {
            App::getInstance()->log()->debug($this->_dumpCondition($bind), \Bluefin\Log::CHANNEL_DIAG);
        }
        //[-]DEBUG

        $sql = $this->_adapter->buildUpdateSQL($relations, $values, $where);

        return $this->_adapter->query($sql, $bind);
    }

    public function delete(ModelMetadata $schema, $condition, $physical = false)
    {
        if ($schema->hasFeature(Convention::FEATURE_LOGICAL_DELETION) && !$physical)
        {
            $isDeletedFieldName = $schema->getFeatureContext(Convention::FEATURE_LOGICAL_DELETION);

            return self::update($schema, array($isDeletedFieldName => 1), $condition);
        }

        $relations = new Relations($schema);
        $where = $this->_normalizeCondition($relations, $condition);
        //[+]DEBUG
        if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
        {
            App::getInstance()->log()->debug($this->_dumpCondition($condition), \Bluefin\Log::CHANNEL_DIAG);
        }
        //[-]DEBUG

        $sql = $this->_adapter->buildDeleteSQL($relations, $where);

        return $this->_adapter->query($sql, $condition);
    }

    public function extractColumnsMetadata(ModelMetadata $mainTableMeta, $columns)
    {
        $relations = new Relations($mainTableMeta);

        $selectOutput = [];
        $relations->splitRelationships($columns, $selectOutput);

        $this->_normalizeSelectedColumns($relations, $selectOutput);

        return $selectOutput;
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
    public function buildSelectSQL(&$selected, $from, &$where = null, array $grouping = null, array $ranking = null, array $pagination = null)
    {
        $select = $this->_buildSelect($selected, $from, $where, $grouping, $ranking);
        return $this->_adapter->buildSelectSQL($select, $pagination);
    }

    public function buildSelectSQLWithCount(&$selected, $from, &$where = null, array $grouping = null, array $ranking = null, array &$pagination = null)
    {
        is_array($pagination) || ($pagination = []);

        $select = $this->_buildSelect($selected, $from, $where, $grouping, $ranking);
        $sql2 = $this->_adapter->buildSelectSQL($select, $pagination);

        $pkColumn = $select->getRelations()->getTableMetadata()->getPrimaryKey();
        $pkColumn = $this->_quoteColumn($select->getRelations(), null, $pkColumn);

        $select->setSelect("COUNT({$pkColumn})");
        $select->setOrderBy(null);

        return array($this->_adapter->buildSelectSQL($select), $sql2);
    }

    protected function _buildSelect(&$selected, $from, &$where = null, array $grouping = null, array $ranking = null)
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
        $selected = $selectOutput;

        if (isset($where))
        {
            $terms = $this->_normalizeCondition($relations, $where);
            //[+]DEBUG
            if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
            {
                App::getInstance()->log()->debug($this->_dumpCondition($where), \Bluefin\Log::CHANNEL_DIAG);
            }
            //[-]DEBUG

            $select->setWhere($this->_adapter->buildWhereClause($terms));
        }

        $select->setJoin($this->_adapter->buildJoinRelations($relations));

        isset($grouping) && $select->setGroupBy($this->_adapter->buildGroupByClause($grouping));
        isset($ranking) && $select->setOrderBy($this->_adapter->buildOrderByClause($ranking));

        return $select;
    }

    /**
     * 规范化查询条件，将dot.name形式展开成表间连接，支持自定义子句（DbCondition）、或子句（DbClauseOr）、否定值（DbExprNot）和函数值（DbExpr）。
     * @param Relations $relations
     * @param $condition
     * @return array|string
     * @throws \Bluefin\Exception\InvalidOperationException
     */
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
                if ($term instanceof DbClauseOr)
                {
                    /**
                     * @var DbClauseOr $term
                     */
                    $subCondition = $term->getExpressions();
                    $subTerms = $this->_normalizeCondition($relations, $subCondition);
                    $values = array_merge($values, $subCondition);
                    $terms[] = $this->_adapter->combineOrCondition($subTerms);
                    continue;
                }

                // $term is the full condition
                if ($term instanceof DbCondition)
                {
                    /**
                     * @var \Bluefin\Data\DbCondition $term
                     */
                    $terms[] = '(' . $term . ')';
                    continue;
                }

                throw new \Bluefin\Exception\InvalidOperationException(
                    "Invalid db condition!"
                );
            }

            // 否定语句标志
            $negative = false;

            if ($term instanceof DbExprNot)
            {//值是个否定语句
                /**
                 * @var \Bluefin\Data\DbExprNot $term
                 */
                $term = $term->__toString();
                $negative = true;
            }

            if ($column[0] == Convention::DB_EXPR_VT_TAG)
            {
                $column = Convention::DB_EXPR_TAG . \Bluefin\VarText::parseVarText(mb_substr($column, 1));
            }

            if ($column[0] == Convention::DB_EXPR_TAG)
            {
                $column = mb_substr($column, 1);
                $tablePath = null;
                $keyIsExpr = true;
            }
            else
            {
                $tablePath = $this->_extractTablePathFromColumnName($column);
                $keyIsExpr = false;
            }

            if (is_array($term))
            {//IN 语句的值
                if (empty($term)) continue;

                if ($keyIsExpr)
                {
                    foreach ($term as &$value)
                    {
                        $value = $this->_adapter->quoteValue($value);
                    }
                }
                else
                {
                    $fieldOption = $relations->getFieldOptions($tablePath, $column);
                    if (!isset($fieldOption)) continue;

                    foreach ($term as &$value)
                    {
                        Type::convertValue($value, $fieldOption);
                        $value = $this->_adapter->quoteValue($value);
                    }

                    $column = $this->_quoteColumn($relations, $tablePath, $column);
                }

                $terms[] = $this->_adapter->combineCondition($column, $term, $negative);
                continue;
            }

            if ($term instanceof DbExpr)
            {//值是个表达式
                /**
                 * @var DbExpr $term
                 */
                if ($term->isVarText())
                {
                    $term = \Bluefin\VarText::parseVarText($term->__toString());
                }
                else
                {
                    $term = $term->__toString();
                }
            }
            else if (isset($term))
            {//其他有值情况
                if ($keyIsExpr)
                {
                    $term = $this->_adapter->quoteValue($term);
                }
                else
                {
                    $fieldOption = $relations->getFieldOptions($tablePath, $column);
                    if (!isset($fieldOption)) continue;

                    if (array_key_exists(Type::ATTR_ENUM, $fieldOption) ||
                        array_key_exists(Type::ATTR_STATE, $fieldOption))
                    {
                        if ($term == Database::KW_ALL_STATES)
                        {
                            continue;
                        }
                    }

                    isset($values[$tablePath]) || ($values[$tablePath] = []);
                    $values[$tablePath][$column] = $term;
                    $term = '?';
                }
            }

            if (!$keyIsExpr)
            {
                $column = $this->_quoteColumn($relations, $tablePath, $column);
            }

            $terms[] = $this->_adapter->combineCondition($column, $term, $negative);
        }

        $condition = [];

        foreach ($values as $tablePath => &$tableData)
        {
            $metadata = $relations->getTableMetadata($tablePath);
            $tableData = $metadata->filter(array_keys($tableData), $tableData, false, true);

            foreach ($tableData as $column => $value)
            {
                $condition[] = [$value, $metadata->getFilterOption($column)[Type::ATTR_TYPE]];
            }
        }

        return $terms;
    }

    protected function _extractTablePathFromColumnName(&$columnName)
    {
        $pos = mb_strrpos($columnName, '.');

        if (false === $pos)
        {
            return '';
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
            App::assert($tablePath == '');
            $columnName = $this->_adapter->quoteIdentifier($columnName);
        }

        return $columnName;
    }

    /**
     * 规范化查询的数据列，将dot.name形式展开成表间连接。
     * @param Relations $relations 表关系数据结构。
     * @param array $selected 返回最终输出的字段及其元数据。
     * @return string
     * @throws \Bluefin\Exception\InvalidOperationException
     */
    protected function _normalizeSelectedColumns(Relations $relations, array &$selected)
    {
        $output = [];
        $outputColumns = [];

        foreach ($selected as $columnName => $alias)
        {
            if ($columnName[0] == Convention::DB_EXPR_VT_TAG)
            {
                $columnName = Convention::DB_EXPR_TAG . \Bluefin\VarText::parseVarText(mb_substr($columnName, 1));
            }

            if ($columnName[0] == Convention::DB_EXPR_TAG)
            {
                $columnName = mb_substr($columnName, 1);

                if (isset($alias))
                {
                    $output[] = $this->_adapter->combineColumnAndAlias($columnName, $alias);
                    $outputColumns[$alias] = null;
                }
                else
                {
                    $output[] = $columnName;
                    $outputColumns[$columnName] = null;
                }

                continue;
            }

            $tablePath = $this->_extractTablePathFromColumnName($columnName);
            $fieldOptions = $relations->getFieldOptions($tablePath, $columnName);
            if (!isset($fieldOptions))
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Unknown column: {$columnName}!"
                );
            }

            $shortColumnName = $columnName;
            $columnName = $this->_quoteColumn($relations, $tablePath, $columnName);

            $wrapped = $this->getAdapter()->wrapColumnOnSelect($fieldOptions[Type::ATTR_TYPE], $columnName);

            if (!isset($alias) && $wrapped != $columnName)
            {
                $alias = $shortColumnName;
                $columnName = $wrapped;
            }

            if (isset($alias))
            {
                $output[] = $this->_adapter->combineColumnAndAlias($columnName, $alias);
                $outputColumns[$alias] = $fieldOptions;
            }
            else
            {
                $output[] = $columnName;
                $outputColumns[$shortColumnName] = $fieldOptions;
            }
        }

        $selected = $outputColumns;

        return implode(',', $output);
    }

    /**
     * 导出字段和值绑定数组。
     *
     * @param ModelMetadata $schema
     * @param array $bind [IN/OUT] 输入INSERT字段的键值对，输出INSERT字段占位符对应的值（按字段占位符出现的次序）。
     * @param array $cols [OUT] 输出INSERT字段名称数组（字段名称已括引）。
     * @return array INSERT语句VALUES子句的字段值或字段占位符。
     */
    protected function _prepareInsertParams(ModelMetadata $schema, array &$bind, array &$cols)
    {
        $values = array();
        $bindValues = array();

        foreach ($bind as $col => $val)
        {
            if ($val instanceof DbExpr)
            {//如果该值是一个数据库调用

                if ($val->isVarText())
                {
                    $val = \Bluefin\VarText::parseVarText($val->__toString(), $bind);
                }
                else
                {
                    $val = $val->__toString();
                }
            }
            else
            {
                $fieldOption = $schema->getFilterOption($col);
                if (!isset($fieldOption)) continue;

                Type::validateValue($val, $fieldOption);
                $bindValues[] = [ $val, $fieldOption[Type::ATTR_TYPE] ];

                $val = '?';
            }

            $values[] = $val;
            $cols[] = $this->_adapter->quoteIdentifier($col);
        }

        $bind = $bindValues;

        return $values;
    }

    /**
     * 准备UPDATE语句的参数。
     *
     * @param ModelMetadata $schema
     * @param array $bind [IN/OUT] 输入UPDATE字段的键值对，输出UPDATE字段的占位符对应的值（按占位符出现的次序）。
     * @return array UPDATE语句的SET子句的字段数值。
     */
    protected function _prepareUpdateParams(ModelMetadata $schema, array &$bind)
    {
        $values = array();
        $bindValues = array();

        foreach ($bind as $col => $val)
        {
            if ($val instanceof DbExpr)
            {//如果该值是一个数据库调用

                if ($val->isVarText())
                {
                    $val = \Bluefin\VarText::parseVarText($val->__toString(), $bind);
                }
                else
                {
                    $val = $val->__toString();
                }
            }
            else
            {
                $fieldOption = $schema->getFilterOption($col);

                //如果该字段不是该表所有
                if (!isset($fieldOption)) continue;

                isset($val) && Type::validateValue($val, $fieldOption);
                $bindValues[] = [ $val, $fieldOption[Type::ATTR_TYPE] ];

                $val = '?';
            }

            $values[] = $this->_adapter->quoteIdentifier($col) . '=' . $val;
        }

        $bind = $bindValues;

        return $values;
    }

    private function _dumpCondition(array $dbParams)
    {
        foreach ($dbParams as &$param)
        {
            if ($param[1] == Type::TYPE_UUID)
            {
                $param[0] = bin2hex($param[0]);
            }
        }

        return "PARAMS: " . json_encode($dbParams);
    }
}