<?php

namespace Bluefin\Data\Db;

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
     * @param null $type
     */
    function quoteValue($value, $type = null);

    /**
     * @abstract
     * @param $quotedColumn
     * @param $value
     */
    function combineCondition($quotedColumn, $value);

    /**
     * @abstract
     * @param $tableName
     * @param $columnName
     * @return void
     */
    function combineTableAndColumn($tableName, $columnName);

    /**
     * @abstract
     * @param $columnName
     * @param $alias
     */
    function combineColumnAndAlias($columnName, $alias);

    /**
     * @abstract
     * @param $table
     * @param array $columns
     * @param array $values
     */
    function buildInsertSQL($table, array $columns, array $values);

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
     * @abstract
     * @param $type
     * @param $columnName
     * @return string
     */
    function wrapColumnOnSelect($type, $columnName);
}
