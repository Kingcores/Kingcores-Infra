<?php

namespace Bluefin\Data\Db;

use Bluefin\Data\DataBase;
use Bluefin\Data\Select;
use Bluefin\Data\Relations;
 
interface DbInterface 
{
    /**
     * @abstract
     * @param $idName
     * @return string
     */
    function quoteIdentifier($idName);

    /**
     * @abstract
     * @param $value
     * @return mixed
     */
    function quoteValue($value);

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $fetchMode
     * @return mixed
     */
    function fetchAll($sql, array $params = null, $fetchMode = Database::FETCH_ALL_TO_ASSOC);

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $fetchMode
     * @return mixed
     */
    function fetchRow($sql, array $params = null, $fetchMode = Database::FETCH_ROW_TO_ASSOC);

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $columnIndex
     * @return mixed
     */
    function fetchValue($sql, array $params = null, $columnIndex = 0);

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $columnIndex
     * @param bool $unique
     * @return mixed
     */
    function fetchColumn($sql, array $params = null, $columnIndex = 0, $unique = false);

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $groupByColumnIndex
     * @return array
     */
    function fetchGroup($sql, array $params = null, $groupByColumnIndex = 0);

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @return int Returns affected row count.
     */
    function query($sql, array $params = null);

    /**
     * @abstract
     * @param null $name
     * @return mixed
     */
    function lastInsertId($name = null);

    /**
     * @return void
     */
    function beginTransaction();

    /**
     * @return void
     */
    function commit();

    /**
     * @return void
     */
    function rollback();

    /**
     * @abstract
     * @param $quotedColumn
     * @param $value
     * @param bool $negative
     * @return string
     */
    function combineCondition($quotedColumn, $value, $negative = false);

    /**
     * @param array $terms
     * @return mixed
     */
    function combineOrCondition(array $terms);

    /**
     * @abstract
     * @param $tableName
     * @param $columnName
     * @return string
     */
    function combineTableAndColumn($tableName, $columnName);

    /**
     * @abstract
     * @param $columnName
     * @param $alias
     * @return string
     */
    function combineColumnAndAlias($columnName, $alias);

    /**
     * @param $table
     * @param array $columns
     * @param array $values
     * @param array $onDuplicateUpdate
     * @return mixed
     */
    function buildInsertSQL($table, array $columns, array $values, array $onDuplicateUpdate = null);

    /**
     * @abstract
     * @param \Bluefin\Data\Relations $relations
     * @param $set
     * @param $where
     */
    function buildUpdateSQL(Relations $relations, $set, $where);

    /**
     * @abstract
     * @param \Bluefin\Data\Relations $relations
     * @param $where
     */
    function buildDeleteSQL(Relations $relations, $where);

    /**
     * @abstract
     * @param \Bluefin\Data\Select $select
     * @param array|null $pagination
     * @return string
     */
    function buildSelectSQL(Select $select, array &$pagination = null);

    /**
     * @abstract
     * @param array $where
     */
    function buildWhereClause($where);

    /**
     * @abstract
     * @param array $grouping
     * @return string
     */
    function buildGroupByClause(array $grouping);

    /**
     * @abstract
     * @param array $ranking
     * @return string
     */
    function buildOrderByClause(array $ranking);

    /**
     * @abstract
     * @param \Bluefin\Data\Relations $relations
     */
    function buildJoinRelations(Relations $relations);

    /**
     * 封装一些特殊的字段。
     *
     * @abstract
     * @param $type
     * @param $columnName
     * @return string
     */
    function wrapColumnOnSelect($type, $columnName);
}
