<?php

namespace Bluefin\Data;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Common;

//TODO: formatter, pre-commit and post-fetch

abstract class Model
{
    const OP_CREATE = 'create';
    const OP_GET = 'get';
    const OP_DELETE = 'delete';
    const OP_UPDATE = 'update';

    const ACL_ACCEPTED = 0;
    const ACL_UNAUTHORIZED = Common::HTTP_UNAUTHORIZED;
    const ACL_ACTION_NOT_ALLOWED = Common::HTTP_METHOD_NOT_ALLOWED;
    const ACL_FORBIDDEN = Common::HTTP_FORBIDDEN;

    /**
     * @static
     * @return \Bluefin\Data\ModelMetadata
     */
    public static function s_metadata()
    {
        App::assert(false);
    }

    /**
     * 获取一行结果。
     *
     * @param $selected 结果集的字段，支持关系扩展。
     * @param null $condition 查询条件。
     * @param array $grouping 分组条件。
     * @param array $ranking 排序条件。
     * @param int $top 按照排序条件获取第几条。
     * @param array $outputColumns
     * @return mixed 键值对。
     * @throws \Bluefin\Exception\InvalidOperationException
     */
    public static function fetchOneRow($selected, $condition = null, array $grouping = null, array $ranking = null, $top = 1, array &$outputColumns = null)
    {
        $schema = static::s_metadata();

        if (is_array($condition))
        {
            if (in_array(Database::KW_SQL_CONDITION_LATEST, $condition))
            {
                if (!$schema->hasFeature(Convention::FEATURE_CREATE_TIMESTAMP))
                {
                    throw new \Bluefin\Exception\InvalidOperationException(
                        "'@LATEST' condition is allowed for entity without 'create_timestamp' feature."
                    );
                }

                unset($condition[Database::KW_SQL_CONDITION_LATEST]);
                isset($ranking) || ($ranking = []);

                $ctFieldName = $schema->getFeatureContext(Convention::FEATURE_CREATE_TIMESTAMP);
                $ranking[$ctFieldName] = true;
            }
        }

        $sql = $schema->getDatabase()->buildSelectSQL(
            $selected,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            array(Database::KW_SQL_ROWS_PER_PAGE => 1, Database::KW_SQL_PAGE_INDEX =>(int)$top)
        );

        $outputColumns = $selected;

        return $schema->getDatabase()->getAdapter()->fetchRow($sql, $condition);
    }

    /**
     * 获取一列。
     *
     * @param $columnName
     * @param null $condition
     * @param bool $unique
     * @param array $grouping
     * @param array $ranking
     * @return mixed
     */
    public static function fetchColumn($columnName, $condition = null, $unique = false, array $grouping = null, array $ranking = null)
    {
        $schema = static::s_metadata();

        $sql = $schema->getDatabase()->buildSelectSQL(
            $columnName,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking
        );

        return $schema->getDatabase()->getAdapter()->fetchColumn($sql, $condition, 0, $unique);
    }

    /**
     * 获取多行结果。
     *
     * @param $selected 结果集的字段，支持关系扩展。
     * @param null $condition 查询条件。
     * @param array $grouping 分组条件。
     * @param array $ranking 排序条件。
     * @param array $pagination 分页条件。
     * @param array $outputColumns
     * @param bool $withDeleted
     * @return mixed 键值对数组。
     */
    public static function fetchRows($selected, $condition = null, array $grouping = null, array $ranking = null, array $pagination = null, array &$outputColumns = null, $withDeleted = false)
    {
        $schema = static::s_metadata();

        if (!$withDeleted && $schema->hasFeature(Convention::FEATURE_LOGICAL_DELETION))
        {
            isset($condition) || ($condition = []);
            $logicalDeletionField = $schema->getFeatureContext(Convention::FEATURE_LOGICAL_DELETION);
            $condition[$logicalDeletionField] = false;
        }

        $sql = $schema->getDatabase()->buildSelectSQL(
            $selected,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            $pagination
        );

        $outputColumns = $selected;

        return $schema->getDatabase()->getAdapter()->fetchAll($sql, $condition);
    }

    /**
     * 获取带记录总条数的多行结果。
     *
     * @param $selected 结果集的字段，支持关系扩展。
     * @param null $condition 查询条件。
     * @param array $grouping 分组条件。
     * @param array $ranking 排序条件。
     * @param array $pagination [IN/OUT] 输入分页条件，返回总记录数和分页情况。
     * @param array $outputColumns
     * @param bool $withDeleted
     * @return mixed 键值对数组。
     */
    public static function fetchRowsWithCount($selected, $condition = null, array $grouping = null, array $ranking = null, array &$pagination = null, array &$outputColumns = null, $withDeleted = false)
    {
        $schema = static::s_metadata();

        Database::extractQueryCondition($condition, $outputColumns, $pagination, $ranking);

        if (!$withDeleted && $schema->hasFeature(Convention::FEATURE_LOGICAL_DELETION))
        {
            isset($condition) || ($condition = []);
            $logicalDeletionField = $schema->getFeatureContext(Convention::FEATURE_LOGICAL_DELETION);
            $condition[$logicalDeletionField] = false;
        }

        list($sqlCount, $sql) = $schema->getDatabase()->buildSelectSQLWithCount(
            $selected,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            $pagination
        );

        $outputColumns = $selected;

        $pagination[Database::KW_SQL_TOTAL_ROWS] = $schema->getDatabase()->getAdapter()->fetchValue($sqlCount, $condition);
        $pagination[Database::KW_SQL_TOTAL_PAGES] = array_key_exists(Database::KW_SQL_ROWS_PER_PAGE, $pagination) ?
            ($pagination[Database::KW_SQL_TOTAL_ROWS] > 0 ?
            ceil($pagination[Database::KW_SQL_TOTAL_ROWS]/$pagination[Database::KW_SQL_ROWS_PER_PAGE]) : 1) : 1;

        return $schema->getDatabase()->getAdapter()->fetchAll($sql, $condition);
    }

    /**
     * 获取一个字段的结果。
     *
     * @param $fieldName 字段名称，支持关系扩展。
     * @param null $condition 查询条件。
     * @param array $grouping 分组条件。
     * @param array $ranking 排序条件。
     * @param int $top 按照排序条件获取第几条。
     * @return mixed 一个值。
     */
    public static function fetchValue($fieldName, $condition = null, array $grouping = null, array $ranking = null, $top = 1)
    {
        \Bluefin\App::assert(!is_array($fieldName) && isset($fieldName));

        $schema = static::s_metadata();

        $sql = $schema->getDatabase()->buildSelectSQL(
            $fieldName,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            array(Database::KW_SQL_ROWS_PER_PAGE => 1, Database::KW_SQL_PAGE_INDEX =>(int)$top)
        );

        return $schema->getDatabase()->getAdapter()->fetchValue($sql, $condition);
    }

    public static function fetchCount($condition = null, array $grouping = null)
    {
        return self::fetchValue(new DbExpr('COUNT(' . static::s_metadata()->getDatabase()->getAdapter()->quoteIdentifier(static::s_metadata()->getPrimaryKey()) . ')'), $condition, $grouping);
    }

    public static function checkActionPermission($action, array $row = null)
    {
        $metadata = static::s_metadata();
        $acl = $metadata->getAcl($action);
        $stateField = $metadata->getFeatureContext('has_states');

        if ($action[0] == '_')
        {
            _ARG_EXISTS($stateField, $row);

            $currentState = $row[$stateField];
            if (!array_key_exists($currentState, $acl))
            {
                return Model::ACL_ACTION_NOT_ALLOWED;
            }

            $allRoles = $acl[$currentState];
        }
        else
        {
            $allRoles = $acl;
        }

        if (!isset($allRoles)) return Model::ACL_FORBIDDEN;
        if ($allRoles == Convention::KEYWORD_ALL_ROLES) return Model::ACL_ACCEPTED;

        $app = App::getInstance();

        //Authorization checking
        foreach ($allRoles as $auth => $roles)
        {
            $currentRoles = $app->role($auth)->get();
            isset($currentRoles) || ($currentRoles = []);

            if ($app->getRegistry(Convention::KEYWORD_SYSTEM_ROLE, false))
            {
                array_push_unique($currentRoles, '*system*');
            }
            if ($app->getRegistry(Convention::KEYWORD_VENDOR_ROLE, false))
            {
                array_push_unique($currentRoles, '*vendor*');
            }

            $currentUID = $app->auth($auth)->getUniqueID();
            if (isset($currentUID))
            {
                array_push_unique($currentRoles, '*any*');

                if (isset($row))
                {
                    if ($metadata->hasFeature('owner_field'))
                    {
                        isset($ownerField) || ($ownerField = $metadata->getFeatureContext('owner_field'));

                        if (array_key_exists($ownerField, $row) && $row[$ownerField] == $currentUID)
                        {
                            array_push_unique($currentRoles, '*owner*');
                        }
                    }

                    if ($metadata->hasFeature('creator_field'))
                    {
                        isset($creatorField) || ($creatorField = $metadata->getFeatureContext('creator_field'));

                        if (array_key_exists($creatorField, $row) && $row[$creatorField] == $currentUID)
                        {
                            array_push_unique($currentRoles, '*creator*');
                        }
                    }
                }
            }

            $overlappedRoles = array_intersect($roles, $currentRoles);
            if (!empty($overlappedRoles))
            {
                return Model::ACL_ACCEPTED;
            }
        }

        return Model::ACL_UNAUTHORIZED;
    }

    public static function isActionAllowed($action, array $row = null)
    {
        return self::ACL_ACCEPTED === self::checkActionPermission($action, $row);
    }

    public static function requireActionPermission($action, array $row = null)
    {
        $status = self::checkActionPermission($action, $row);
        if ($status !== self::ACL_ACCEPTED)
        {
            throw new \Bluefin\Exception\RequestException(null, $status);
        }
    }

    public static function addRoleCondition($action, array &$condition)
    {
        $metadata = static::s_metadata();
        $acl = $metadata->getAcl($action);
        if (!array_key_exists('roles', $acl)) return;

        $app = App::getInstance();
        $rolesCondition = [];

        foreach ($acl['roles'] as $auth => $roles)
        {
            $currentUID = $app->auth($auth)->getUniqueID();
            if (!isset($currentUID))
            {
                throw new \Bluefin\Exception\UnauthorizedException();
            }

            if (in_array('*owner*', $roles))
            {
                $rolesCondition[$metadata->getFeatureContext('owner_field')] = $currentUID;
            }

            if (in_array('*creator*', $roles))
            {
                $rolesCondition[$metadata->getFeatureContext('creator_field')] = $currentUID;
            }
        }

        if (count($rolesCondition) == 1)
        {
            $condition = array_merge($condition, $rolesCondition);
        }
        else
        {
            $condition[] = new \Bluefin\Data\DbClauseOr($rolesCondition);
        }
    }

    /**
     * @var bool Indicates whether the data is retrieved from DB
     */
    protected $_isPopulated = false; // get from db

    /**
     * @var array
     */
    protected $_data = array();

    /**
     * @var array
     */
    protected $_links = array();

    /**
     * @var array
     */
    protected $_modifiedFields = [];

    /**
     * @static
     * @return \Bluefin\Data\ModelMetadata
     */
    protected $_metadata;

    public function __construct(ModelMetadata $metadata)
    {
        $this->_metadata = $metadata;
    }

    public function __get($name)
    {
        return array_try_get($this->_data, $name);
    }

    public function __set($name, $value)
    {
        if (!in_array($name, $this->_metadata->getFieldNames()))
        {
            return;
        }

        $this->_data[$name] = $value;
        array_push_unique($this->_modifiedFields, $name);
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
        array_erase($this->_modifiedFields, $name);
    }

    public function fieldValueExists($name)
    {
        return array_key_exists($name, $this->_data);
    }

    public function metadata()
    {
        return $this->_metadata;
    }

    public function pk()
    {
        return $this->__get($this->metadata()->getPrimaryKey());
    }

    public function isEmpty()
    {
        return empty($this->_data);
    }

    public function data()
    {
        return $this->_data;
    }

    public function modifiedData($reset = false)
    {
        $modified = $this->_modifiedFields;
        $reset && ($this->_modifiedFields = []);
        return array_get_all($this->_data, $modified);
    }

    public function markAllModified()
    {
        $this->_modifiedFields = array_keys($this->_data);
    }

    public function reset($data = null, $dbPopulated = false)
    {
        if (isset($data))
        {
            $this->_data = $data;
        }
        else
        {
            $this->_data = [];
        }

        $this->_links = [];
        $this->_modifiedFields = [];
        $this->_isPopulated = $dbPopulated;

        return $this;
    }

    public function apply(array $data, $resetModifiedBefore = false, $resetModifiedAfter = false)
    {
        if ($resetModifiedBefore)
        {
            $this->_modifiedFields = [];
        }

        if (isset($data))
        {
            foreach ($data as $key => $value)
            {
                $this->__set($key, $value);
            }
        }

        if ($resetModifiedAfter)
        {
            $this->_modifiedFields = [];
        }

        return $this;
    }

    public function populate(array $data)
    {
        $this->reset($data, true);
    }

    public function load($condition)
    {
        if (!is_array($condition))
        {
            $condition = array($this->_metadata->getPrimaryKey() => $condition);
        }

        $result = self::fetchOneRow(['*'], $condition);

        if (false === $result)
        {
            $this->reset();
            return false;
        }

        $this->populate($result);
        return true;
    }

    /**
     * @param bool $newRecord 是否新记录。
     * @return array
     * @throws \Bluefin\Exception\InvalidRequestException
     */
    public function filter($newRecord = false)
    {
        if ($newRecord)
        {
            $fields = $this->_metadata->getFieldNames();
        }
        else
        {
            $fields = $this->_modifiedFields;
        }

        return $this->_metadata->filter($fields, $this->_data, $newRecord);
    }

    public function save()
    {
        if ($this->_isPopulated)
        {
            return $this->update();
        }
        else
        {
            return $this->insert(true);
        }
    }

    public function insert($updateIfDuplicate = false)
    {
        $db = $this->_metadata->getDatabase()->getAdapter();

        if ($updateIfDuplicate)
        {
            $uniqueKeys = $this->_metadata->getFeatureContext('unique_keys');
            isset($uniqueKeys) || ($uniqueKeys = []);
            array_unshift($uniqueKeys, [ $this->_metadata->getPrimaryKey() ]);

            $backupData = $this->_data;
            $db->beginTransaction();

            try
            {
                foreach ($uniqueKeys as $keyFields)
                {
                    $condition = array_get_all($this->_data, $keyFields);
                    if (count($condition) == count($keyFields))
                    {
                        $this->markAllModified();

                        $affected = $this->update($condition);

                        if ($affected > 0)
                        {
                            $db->commit();

                            return 2;
                        }
                    }

                    $this->reset($backupData);
                }

                $affected = $this->insert();

                $db->commit();

                return $affected;
            }
            catch (\Exception $e)
            {
                $db->rollback();

                throw $e;
            }
        }

        $triggers = $this->_metadata->getFeatureContext('triggers');
        isset($triggers) || ($triggers = []);

        $hasBeforeInsertTrigger = in_array('BEFORE-INSERT', $triggers);
        $hasAfterInsertTrigger = in_array('AFTER-INSERT', $triggers);

        $withTransaction = $hasBeforeInsertTrigger || $hasAfterInsertTrigger;

        $data = $this->filter(true);

        if ($withTransaction)
        {
            $db->beginTransaction();
        }

        if ($hasBeforeInsertTrigger)
        {
            $this->apply($data, true);
            $this->_beforeInsert();
            $data = $this->modifiedData(true);
        }

        try
        {
            $affected = $this->_metadata->getDatabase()->insert($this->_metadata, $data);
            if (0 === $affected)
            {
                throw new \Bluefin\Exception\DatabaseException(
                    "Inserting data into table '{$this->_metadata->getModelName()}' failed."
                );
            }

            if ($this->_metadata->hasFeature(Convention::FEATURE_AUTO_INCREMENT_ID))
            {
                $id = $this->_metadata->getDatabase()->getAdapter()->lastInsertId($this->_metadata->getModelName(), $this->_metadata->getPrimaryKey());
                $data[$this->_metadata->getPrimaryKey()] = $id;
            }

            $this->apply($data, false, true);

            if ($hasAfterInsertTrigger)
            {
                $this->_afterInsert();
            }

            if ($withTransaction)
            {
                $db->commit();
            }
        }
        catch (\Exception $e)
        {
            if ($withTransaction)
            {
                $db->rollback();
            }

            throw $e;
        }

        return $affected;
    }

    public function update($condition = null)
    {
        if (!isset($condition))
        {
            $pkValue = $this->pk();
            if (!isset($pkValue))
            {
                throw new \Bluefin\Exception\InvalidOperationException('The value of primary key is required!');
            }

            $condition = [$this->_metadata->getPrimaryKey() => $pkValue];
        }
        else if (!is_array($condition))
        {
            $condition = [$this->_metadata->getPrimaryKey() => $condition];
        }
        else
        {//is_array
            $pkFieldName = $this->_metadata->getPrimaryKey();

            if (count($condition) > 1 || !array_key_exists($pkFieldName, $condition))
            {//batch-update
                //get all pk values of rows to update
                $pkArray = self::fetchColumn($pkFieldName, $condition);

                //backup current record
                $modified = array_get_all($this->_data, $this->_modifiedFields);

                $affected = 0;

                foreach ($pkArray as $pkValue)
                {
                    $this->apply($modified);
                    $this->update($pkValue);

                    $affected++;
                }

                return $affected;
            }
        }

        if (!$this->_isPopulated)
        {
            $modified = array_get_all($this->_data, $this->_modifiedFields);
            if (!$this->load($condition)) return 0;
            $this->apply($modified);
        }

        $this->__unset($this->_metadata->getPrimaryKey());
        $affected = 0;

        $data = $this->filter();
        if (!empty($data))
        {
            $triggers = $this->_metadata->getFeatureContext('triggers');
            isset($triggers) || ($triggers = []);

            $hasBeforeUpdateTrigger = in_array('BEFORE-UPDATE', $triggers);
            $hasAfterUpdateTrigger = in_array('AFTER-UPDATE', $triggers);

            $withTransaction = $hasBeforeUpdateTrigger || $hasAfterUpdateTrigger;

            if ($withTransaction)
            {
                $db = $this->_metadata->getDatabase()->getAdapter();
                $db->beginTransaction();
            }

            try
            {
                if ($hasBeforeUpdateTrigger)
                {
                    $this->apply($data, true);
                    $this->_beforeUpdate();
                    $data = $this->modifiedData(true);
                }

                $affected = $this->_metadata->getDatabase()->update($this->_metadata, $data, $condition);
                $data[$this->_metadata->getPrimaryKey()] = $condition[$this->_metadata->getPrimaryKey()];
                $this->apply($data, false, true);

                if ($hasAfterUpdateTrigger)
                {
                    $this->_afterUpdate();
                }

                if ($withTransaction)
                {
                    $db->commit();
                }
            }
            catch (\Exception $e)
            {
                if ($withTransaction)
                {
                    $db->rollback();
                }

                throw $e;
            }
        }

        return $affected;
    }

    public function delete($condition = null)
    {
        if (!isset($condition))
        {
            $pkValue = $this->pk();
            if (!isset($pkValue))
            {
                throw new \Bluefin\Exception\InvalidOperationException('The value of primary key is required!');
            }

            $condition = [$this->_metadata->getPrimaryKey() => $pkValue];
        }
        else if (!is_array($condition))
        {
            $condition = [$this->_metadata->getPrimaryKey() => $condition];
        }
        else
        {//is_array
            $pkFieldName = $this->_metadata->getPrimaryKey();

            if (count($condition) > 1 || !array_key_exists($pkFieldName, $condition))
            {//batch-delete
                //get all pk values of rows to update
                $pkArray = self::fetchColumn($pkFieldName, $condition);

                $affected = 0;

                foreach ($pkArray as $pkValue)
                {
                    $this->reset();
                    $affected += $this->delete($pkValue);
                }

                return $affected;
            }
        }

        if (!$this->_isPopulated && !$this->load($condition))
        {
            return 0;
        }

        $triggers = $this->_metadata->getFeatureContext('triggers');
        isset($triggers) || ($triggers = []);

        $hasBeforeDeleteTrigger = in_array('BEFORE-DELETE', $triggers);
        $hasAfterDeleteTrigger = in_array('AFTER-DELETE', $triggers);

        $withTransaction = $hasBeforeDeleteTrigger || $hasAfterDeleteTrigger;

        if ($withTransaction)
        {
            $db = $this->_metadata->getDatabase()->getAdapter();
            $db->beginTransaction();
        }

        try
        {
            if ($hasBeforeDeleteTrigger)
            {
                $this->_beforeDelete();
            }

            $affected = $this->_metadata->getDatabase()->delete($this->_metadata, $condition, true);

            if ($hasAfterDeleteTrigger)
            {
                $this->_afterDelete();
            }

            if ($withTransaction)
            {
                $db->commit();
            }
        }
        catch (\Exception $e)
        {
            if ($withTransaction)
            {
                $db->rollback();
            }

            throw $e;
        }

        return $affected;
    }

    protected function _beforeInsert()
    {
        App::assert(false, 'Not Implemented!');
    }

    protected function _afterInsert()
    {
        App::assert(false, 'Not Implemented!');
    }

    protected function _beforeUpdate()
    {
        App::assert(false, 'Not Implemented!');
    }

    protected function _afterUpdate()
    {
        App::assert(false, 'Not Implemented!');
    }

    protected function _beforeDelete()
    {
        App::assert(false, 'Not Implemented!');
    }

    protected function _afterDelete()
    {
        App::assert(false, 'Not Implemented!');
    }

    protected function _popColumnAsCondition($columnName)
    {
        if (!in_array($columnName, $this->metadata()->getFieldNames()))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _APP_("Unknown field name: %name%.", ['%name%' => $columnName])
            );
        }

        if (!$this->__isset($columnName))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _APP_('Missing required value "%name%"!', ['%name%' => $columnName])
            );
        }

        $value = $this->__get($columnName);

        $this->__unset($columnName);

        return [$columnName => $value];
    }
}
