<?php
namespace Bluefin\Data;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Data\Database;

class ModelMetadata
{
    private $_db;
    private $_modelName;
    private $_pkName;
    private $_fieldNames;
    private $_fieldOptions;
    private $_features;
    private $_relations;
    private $_m2nRelations;
    private $_acl;

    public function __construct($dbName, $modelName, $pkName,
                                array $fieldOptions,
                                array $features,
                                array $relations,
                                array $m2nRelations,
                                array $acl)
    {
        $this->_db = App::getInstance()->db($dbName);
        $this->_modelName = $modelName;
        $this->_pkName = $pkName;
        $this->_fieldOptions = $fieldOptions;
        $this->_features = $features;
        $this->_relations = $relations;
        $this->_m2nRelations = $m2nRelations;

        $this->_fieldNames = array_keys($fieldOptions);
        $this->_acl = $acl;
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->_modelName;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return _META_($this->_db->getName() . '.' . $this->_modelName);
    }

    /**
     * @return \Bluefin\Data\Database
     */
    public function getDatabase()
    {
        return $this->_db;
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->_pkName;
    }

    /**
     * @return array
     */
    public function getFieldNames()
    {
        return $this->_fieldNames;
    }

    /**
     * @return array
     */
    public function getFilterOptions()
    {
        return $this->_fieldOptions;
    }

    /**
     * @param $fieldName
     * @return mixed
     */
    public function getFilterOption($fieldName)
    {
        return array_try_get($this->_fieldOptions, $fieldName);
    }

    /**
     * @return Model
     */
    public function getModelClass()
    {
        return $this->_db->getModelClass($this->_modelName);
    }

    public function hasFeature($featureName)
    {
        return array_key_exists($featureName, $this->_features);
    }

    public function getFeatureContext($featureName)
    {
        return array_try_get($this->_features, $featureName);
    }

    public function getRelation($fieldName)
    {
        return array_try_get($this->_relations, $fieldName);
    }

    public function getM2NRelation($relationName)
    {
        return array_try_get($this->_m2nRelations, $relationName);
    }

    public function getAcl($actionName = null)
    {
        if (isset($actionName))
        {
            if (!array_key_exists($actionName, $this->_acl))
            {
                throw new \Bluefin\Exception\InvalidOperationException("Unknown action: {$actionName}!");
            }

            return $this->_acl[$actionName];
        }
        else
        {
            return $this->_acl;
        }
    }

    public function expandMeta($columns)
    {
        return $this->_db->extractColumnsMetadata($this, $columns);
    }
    
    public function filter(array $fieldsToFilter, array $data, $newRecord = false, $condition = false)
    {        
        $options = $this->getFilterOptions();

        $result = [];
        $preCommitFilters = [];

        if ($newRecord)
        {
            if ($this->hasFeature('has_states'))
            {
                $stateField = $this->getFeatureContext('has_states');
                $states = $this->getFilterOption($stateField)[Type::ATTR_STATE];
                $defaultState = $states::getDefaultValue();
                $stateTimeField = $defaultState . Convention::STATE_CHANGED_TIME_SUFFIX;
                isset($data[$stateTimeField]) || ($data[$stateTimeField] = date(Type::FORMAT_DATETIME, time()));
                $stateLogField = $stateField . Convention::STATE_CHANGED_HISTORY_SUFFIX;
                isset($data[$stateLogField]) || ($data[$stateLogField] = $defaultState);
            }
        }

        foreach ($fieldsToFilter as $fieldName)
        {
            $fieldOption = $options[$fieldName];
            $postFilter = false;

            if (array_key_exists(Type::ATTR_PRE_COMMIT_FILTER, $fieldOption))
            {
                $preCommitFilters[$fieldName] = $fieldOption[Type::ATTR_PRE_COMMIT_FILTER];
                $postFilter = true;
            }

            $value = array_try_get($data, $fieldName);

            if (!isset($value)) // 输入数据没有该字段或输入值为null
            {
                if ($newRecord)
                {//插入时为空
                    if (array_key_exists(Type::ATTR_INSERT_VALUE, $fieldOption)) // 存在默认值
                    {
                        $initValue = $fieldOption[Type::ATTR_INSERT_VALUE];
                        if ($initValue instanceof \Bluefin\Data\Functor\ProviderInterface)
                        {
                            /**
                             * @var \Bluefin\Data\Functor\ProviderInterface $initValue
                             */
                            $value = $initValue->apply($fieldOption);
                            //echo "{$fieldName} = {$result[$fieldName]}";
                        }
                        else
                        {
                            $value = $initValue;
                        }

                        if (!$postFilter)
                        {
                            Type::convertValue($value, $fieldOption);
                        }

                        $result[$fieldName] = $value;

                        continue;
                    }

                    // 没有默认值
                    if (array_try_get($fieldOption, Type::ATTR_REQUIRED, false)
                        && !array_try_get($fieldOption, Type::ATTR_DB_AUTO_INSERT, false))
                    {
                        $table = $this->getDatabase()->getName() . '.' . $this->getModelName();
                        $field = $table . '.' . $fieldName;

                        // 但是该字段必须有
                        throw new \Bluefin\Exception\InvalidRequestException(
                            _APP_(
                                '"%name%" is required.',
                                array('%name%' => _META_($field))
                            )
                        );
                    }

                    continue;
                }
                else if (array_try_get($fieldOption, Type::ATTR_REQUIRED, false))
                {//更新时不允许空值
                    $table = $this->getDatabase()->getName() . '.' . $this->getModelName();
                    $field = $table . '.' . $fieldName;

                    // 但是该字段必须有
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_(
                            '"%name%" cannot be NULL.',
                            array('%name%' => _META_($field))
                        )
                    );
                }
            }

            // 给定值的情况，无需再考虑Default Value或Update Value
            if ($condition)
            {
                if (!$postFilter && isset($value))
                {
                    Type::convertValue($value, $fieldOption);
                }
            }
            else
            {
                // Check readonly for new record
                if ($newRecord && array_try_get($fieldOption, Type::ATTR_READONLY_ON_INSERTING, false))
                {
                    $table = $this->getDatabase()->getName() . '.' . $this->getModelName();
                    $field = $table . '.' . $fieldName;

                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_(
                            'The initial value of "%name%" cannot be set manually by user.',
                            array('%name%' => _META_($field))
                        )
                    );
                }

                // Check readonly for existing record
                if (!$newRecord && array_try_get($fieldOption, Type::ATTR_READONLY_ON_UPDATING, false))
                {
                    $table = $this->getDatabase()->getName() . '.' . $this->getModelName();
                    $field = $table . '.' . $fieldName;

                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_(
                            '"%name%" is readonly.',
                            array('%name%' => _META_($field))
                        )
                    );
                }

                /**
                 * @var \Bluefin\Data\ValidatorInterface $validator
                 */
                $validator = null;

                if (array_key_exists(Type::ATTR_ENUM, $fieldOption))
                {
                    /**
                     * @var \Bluefin\Data\ValidatorInterface $validator
                     */
                    $validator = $fieldOption[Type::ATTR_ENUM];
                }
                else if (array_key_exists(Type::ATTR_STATE, $fieldOption))
                {
                    $validator = $fieldOption[Type::ATTR_STATE];
                    $result[$value . Convention::STATE_CHANGED_TIME_SUFFIX] = date(Type::FORMAT_DATETIME, time());

                    $stateLogFieldName = $fieldName . Convention::STATE_CHANGED_HISTORY_SUFFIX;

                    $qn = $this->getDatabase()->getAdapter()->quoteIdentifier($stateLogFieldName);

                    $result[$stateLogFieldName] = new DbExpr("CONCAT_WS(',', {$qn}, '{$value}')");
                }

                if (isset($validator) && isset($value))
                {
                    if (!$validator->validate($value))
                    {
                        $table = $this->getDatabase()->getName() . '.' . $this->getModelName();
                        $field = $table . '.' . $fieldName;

                        throw new \Bluefin\Exception\InvalidRequestException(
                            _APP_(
                                'Invalid "%name%".',
                                array('%name%' => _META_($field))
                            )
                        );
                    }
                }
                else if (!$postFilter && isset($value))
                {
                    Type::convertValue($value, $fieldOption);
                }
            }

            $result[$fieldName] = $value;
        }

        if (!empty($preCommitFilters))
        {
            $changes = [];
            if ($newRecord)
            {
                $context = $result;
            }
            else
            {
                $context = array_merge($data, $result);
            }

            foreach ($preCommitFilters as $fieldName => $filter)
            {
                $fieldOption = $this->getFilterOption($fieldName);

                if ($filter instanceof \Bluefin\Data\Functor\ProviderInterface)
                {
                    /**
                     * @var \Bluefin\Data\Functor\ProviderInterface $initValue
                     */
                    $value = $filter->apply($fieldOption, $context);
                }
                else
                {
                    $value = $filter;
                }

                Type::convertValue($value, $fieldOption);

                $changes[$fieldName] = $value;
            }

            $result = array_merge($result, $changes);
        }

        return $result;
    }
}
