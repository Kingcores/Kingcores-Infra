<?php

namespace Bluefin\Data;

use Bluefin\App;
use Bluefin\Convention;

//TODO: formatter, pre-commit and post-fetch

abstract class Model
{
    /**
     * @static
     * @return \Bluefin\Data\ModelMetadata
     */
    public static function s_metadata()
    {
        App::assert(false, Convention::MSG_METHOD_SHOULD_BE_OVERRIDDEN);
    }

    public static function fetchOneRow($selected, $condition = null, array $grouping = null, array $ranking = null, $top = 1)
    {
        $schema = static::s_metadata();

        $sql = $schema->getDatabase()->buildSelectSQL(
            $selected,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            array(Database::KW_SQL_ROWS_PER_PAGE => 1, Database::KW_SQL_PAGE_INDEX =>(int)$top)
        );

        return $schema->getDatabase()->fetchRow($sql, $condition, \Zend_Db::FETCH_ASSOC);
    }

    public static function fetchRows($selected, $condition = null, array $grouping = null, array $ranking = null, array $pagination = null)
    {
        $schema = static::s_metadata();

        $sql = $schema->getDatabase()->buildSelectSQL(
            $selected,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            $pagination
        );

        return $schema->getDatabase()->fetchAll($sql, $condition, \Zend_Db::FETCH_ASSOC);
    }

    public static function fetchRowsWithCount($selected, $condition = null, array $grouping = null, array $ranking = null, array &$pagination = null)
    {
        $schema = static::s_metadata();

        list($sqlCount, $sql) = $schema->getDatabase()->buildSelectSQLWithCount(
            is_array($selected) ? $selected : array($selected),
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            $pagination
        );

        $pagination[Database::KW_SQL_TOTAL_ROWS] = $schema->getDatabase()->fetchOne($sqlCount, $condition);
        $pagination[Database::KW_SQL_TOTAL_PAGES] = array_key_exists(Database::KW_SQL_ROWS_PER_PAGE, $pagination) ?
            ($pagination[Database::KW_SQL_TOTAL_ROWS] > 0 ?
            ceil($pagination[Database::KW_SQL_TOTAL_ROWS]/$pagination[Database::KW_SQL_ROWS_PER_PAGE]) : 1) : 1;

        return $schema->getDatabase()->fetchAll($sql, $condition, \Zend_Db::FETCH_ASSOC);
    }

    public static function scalar($fieldName, array $condition = null, array $grouping = null, array $ranking = null)
    {
        \Bluefin\App::assert(!is_array($fieldName));

        $schema = static::s_metadata();

        $sql = $schema->getDatabase()->buildSelectSQL(
            $fieldName,
            $schema->getModelName(),
            $condition,
            $grouping,
            $ranking,
            array(Database::KW_SQL_ROWS_PER_PAGE => 1)
        );

        return $schema->getDatabase()->fetchOne($sql, $condition);
    }

    public static function pkValue(array $condition)
    {
        return self::scalar(static::s_metadata()->getPrimaryKey(), $condition);
    }

    public static function delete($condition, $physical = false)
    {
        $schema = static::s_metadata();

        is_array($condition) || ($condition = array($schema->getPrimaryKey() => $condition));

        return $schema->getDatabase()->delete($schema, $condition, $physical);
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
    protected $_modifiedFields = array();

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
            //[+]DEBUG
            $tableName = $this->_metadata->getModelName();
            App::getInstance()->log()->debug("Unknown field name. Table: {$tableName}, field: {$name}");
            //[-]DEBUG
            return;
        }

        if (!$this->_isPopulated || $value != $this->_data[$name])
        {
            $this->_data[$name] = $value;
        }

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

    public function get($name)
    {
        return $this->__get($name);
    }

    public function set($name, $value)
    {
        $this->__set($name, $value);
        return $this;
    }

    public function metadata()
    {
        return $this->_metadata;
    }

    public function isEmpty()
    {
        return empty($this->_data);
    }

    public function data($withJoined = false)
    {
        if ($withJoined && !empty($this->_links))
        {
            $data = $this->_data;

            foreach ($this->_links as $fieldName => $joinedData)
            {
                /**
                 * @var Model $joinedData
                 */

                $data[$fieldName] = $joinedData->data(true);
            }

            return $data;
        }

        return $this->_data;
    }

    public function reset($data = null, $dbPopulated = false)
    {
        $this->_data = isset($data) ? $data : array();
        $this->_links = array();
        $this->_isPopulated = $dbPopulated;
        $this->_modifiedFields = (!$dbPopulated && isset($data)) ? array_keys($data) : array();
    }

    public function populate(array $data)
    {
        $this->reset($data, true);
    }

    public function load($condition, $relation = null)
    {
        if (!is_array($condition))
        {
            $condition = array($this->_metadata->getPrimaryKey() => $condition);
        }

        isset($relation) || ($relation = '*');
        $result = self::fetchOneRow($relation, $condition);

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
     * @param array $fields
     * @return array
     * @throws \Bluefin\Exception\InvalidRequestException
     */
    public function filterInputs($newRecord = false, array $fields = null)
    {
        $options = $this->_metadata->getFilterOptions();

        if (isset($fields))
        {
            $options = array_get_all($options, $fields);
        }
        else if (!$newRecord)
        {
            $options = array_get_all($options, $this->_modifiedFields);
        }

        $result = array();
        $postProcessing = array();

        foreach ($options as $fieldName => $fieldOption)
        {
            // 标记是否需要后置处理
            if (array_key_exists(Type::FILTER_POST_PROCESS_FUNCTOR, $fieldOption))
            {
                $postProcessing[$fieldName] = $fieldOption[Type::FILTER_POST_PROCESS_FUNCTOR];
            }

            $value = array_try_get($this->_data, $fieldName);

            //echo $value;

            if (!isset($value)) // 输入数据没有该字段或输入值为null
            {
                if ($newRecord) // insert
                {
                    //echo $fieldName;
                    // 新建记录
                    if (array_key_exists(Type::FILTER_INSERT_VALUE, $fieldOption)) // 存在默认值
                    {
                        $initValue = $fieldOption[Type::FILTER_INSERT_VALUE];
                        if ($initValue instanceof \Bluefin\Data\Functor\SupplierInterface)
                        {
                            /**
                             * @var \Bluefin\Data\Functor\SupplierInterface $initValue
                             */
                            $value = $initValue->supply($fieldOption);
                            //echo "{$fieldName} = {$result[$fieldName]}";
                        }
                        else
                        {
                            $value = $initValue;
                        }

                        Type::convertValue($value, $fieldOption);
                        $result[$fieldName] = $value;

                        continue;
                    }

                    // 没有默认值
                    if (array_try_get($fieldOption, Type::FILTER_REQUIRED, false)
                        && !array_try_get($fieldOption, Type::FILTER_DB_AUTO_INSERT, false)
                        && !array_key_exists(Type::FILTER_POST_PROCESS_FUNCTOR, $fieldOption))
                    {
                        $table = $this->_metadata->getDatabase()->getName() . '.' . $this->_metadata->getModelName();
                        $field = $table . '.' . $fieldName;

                        // 但是该字段必须有
                        throw new \Bluefin\Exception\InvalidRequestException(
                            _T(
                                'Field "%name%" of "%table%" is required.',
                                Convention::LOCALE_BLUEFIN_DOMAIN,
                                array('%name%' => _META_($field), '%table%' => _META_($table))
                            )
                        );
                    }
                }
                else // update
                {
                    if (array_key_exists(Type::FILTER_UPDATE_VALUE, $fieldOption)) // 存在默认值
                    {
                        $updateValue = $fieldOption[Type::FILTER_UPDATE_VALUE];
                        if ($updateValue instanceof \Bluefin\Data\Functor\SupplierInterface)
                        {
                            /**
                             * @var \Bluefin\Data\Functor\SupplierInterface $updateValue
                             */
                            $value = $updateValue->supply($fieldOption);
                        }
                        else
                        {
                            $value = $updateValue;
                        }

                        Type::convertValue($value, $fieldOption);
                        $result[$fieldName] = $value;
                    }
                }

                // 没有默认值，但该字段非必须有
                continue;
            }

            // 给定值的情况，无需再考虑Default Value或Update Value

            // Check readonly for new record
            if ($newRecord && array_try_get($fieldOption, Type::FILTER_READONLY_ON_INSERTING, false))
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T(
                        'The initial value of field "%name%" cannot be set manually by user.',
                        Convention::LOCALE_BLUEFIN_DOMAIN,
                        array('%name%' => $fieldName)
                    )
                );
            }

            // Check readonly for existing record
            if (!$newRecord && array_try_get($fieldOption, Type::FILTER_READONLY_ON_UPDATING, false))
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T(
                        'Field "%name%" is readonly.',
                        Convention::LOCALE_BLUEFIN_DOMAIN,
                        array('%name%' => $fieldName)
                    )
                );
            }

            if (array_key_exists(Type::FILTER_VALIDATOR, $fieldOption))
            {
                if (!$fieldOption[Type::FILTER_VALIDATOR]->validate($value))
                {
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _T(
                            'Invalid value of field "%name%".',
                            Convention::LOCALE_BLUEFIN_DOMAIN,
                            array('%name%' => $fieldName)
                        )
                    );
                }
            }

            Type::convertValue($value, $fieldOption);
            $result[$fieldName] = $value;
        }

        $dataSet = array_merge($this->_data, $result);

        /**
         * @var \Bluefin\Data\Functor\PostProcessorInterface $functor
         */
        foreach ($postProcessing as $postFieldName => $functor)
        {
            $result[$postFieldName] = $functor->process(array_try_get($result, $postFieldName), $dataSet);
        }

        return $result;
    }

    public function insert()
    {
        $data = $this->filterInputs(true);

        //var_dump($data); echo "<br>";

        $affected = $this->_metadata->getDatabase()->insert($this->_metadata, $data);

        if (0 === $affected)
        {
            throw new \Bluefin\Exception\ModelException(
                _T(
                    'Inserting data into table "%name%" failed.',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $this->_metadata->getModelName())
                )
            );
        }

        if ($this->_metadata->hasFeature(Convention::FEATURE_AUTO_INCREMENT_ID))
        {
            $id = $this->_metadata->getDatabase()->getDAO()->lastInsertId($this->_metadata->getModelName(), $this->_metadata->getPrimaryKey());
            $data[$this->_metadata->getPrimaryKey()] = $id;
        }

        $this->reset($data);

        return $affected;
    }

    public function update($byColumn = null)
    {
        isset($byColumn) || ($byColumn = $this->_metadata->getPrimaryKey());

        $condition = $this->_popColumnsAsCondition($byColumn);

        $affected = 0;

        $data = $this->filterInputs();

        if (!empty($data))
        {
            $affected = $this->_metadata->getDatabase()->update($this->_metadata, $data, $condition);
        }

        $data = array_merge($condition, $data);

        $this->reset($data);

        return $affected;
    }

    protected function _popColumnsAsCondition($columnNames)
    {
        is_array($columnNames) || ($columnNames = array($columnNames));

        $result = array();

        foreach ($columnNames as $columnName)
        {
            if (!array_key_exists($columnName, $this->_data))
            {
                throw new \Bluefin\Exception\ModelException(
                    _T(
                        'Column [%field%] is required while updating table [%table%].',
                        Convention::LOCALE_BLUEFIN_DOMAIN,
                        array(
                            '%field%' => _META_($columnName),
                            '%table%' => _META_($this->_metadata->getModelName())
                        )
                    )
                );
            }

            $value = array_try_get($this->_data, $columnName);

            $this->__unset($columnName);

            $result[$columnName] = $value;
        }

        return $result;
    }
}
