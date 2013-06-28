<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Data\Type;
use Bluefin\Lance\Exception\GrammarException;

class Field
{
    private $_entity;
    private $_fieldName;
    private $_fieldType;
    private $_filters;
    private $_ownerFeature;
    private $_fieldTypeWithModifiers;
    private $_isBuiltinType;
    private $_hasIndex;
    private $_isAddedByFeature;
    private $_isReferenceField;
    private $_referencedFieldName;
    private $_onForeignDelete;
    private $_onForeignUpdate;
    private $_isForeignKey;
    private $_defaultValueInDbDefinition;
    private $_configuredName;
    private $_comment;

    public function __construct(Entity $entity, $fieldName, $fieldTypeWithModifiers, $addedByFeature)
    {
        $this->_entity = $entity;
        $this->_fieldName = $fieldName;
        $this->_hasIndex = false;
        $this->_filters = array();
        $this->_isAddedByFeature = $addedByFeature;
        $this->_isReferenceField = false;
        $this->_isForeignKey = false;
        $this->_referencedFieldName = null;

        $this->_processField($fieldTypeWithModifiers);
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function setComment($comment)
    {
        $this->_comment = $comment;

        Convention::addMetadataTranslation($this->_entity->getCommentLocale(), $this->getFullCodeName(),
            $this->getComment());
    }

    public function getDisplayName()
    {
        return Convention::buildDisplayName(
            $this->_entity->getCommentLocale(),
            $this->getFullCodeName(),
            $this->getComment()
        );
    }

    public function isAddedByFeature()
    {
        return $this->_isAddedByFeature;
    }

    public function getConfiguredName()
    {
        return $this->_configuredName;
    }

    public function getFullCodeName()
    {
        return make_dot_name($this->_entity->getFullCodeName(), $this->getFieldName());
    }

    public function getReferenceCopy(Entity $entity, $oldFieldName, $newFieldName)
    {
        $field = clone $this;
        $field->_entity = $entity;
        $field->_configuredName = $oldFieldName;
        $field->_fieldName = $newFieldName;
        $field->setOwnerFeature();
        $field->setInitialValue(null);
        $field->setReadonlyOnCreation(false);
        $field->setReadonlyOnUpdating(false);
        $field->_isReferenceField = true;
        $field->_referencedFieldName = $this->_fieldName;

        $field->_filters[Type::FIELD_NAME] = new PHPCodingLogic("_META_('{$field->getFullCodeName()}')");

        return $field;
    }

    public function isReferenceField()
    {
        return $this->_isReferenceField;
    }

    public function getReferencedFieldName()
    {
        return $this->_referencedFieldName;
    }

    public function getForeignDeletionTrigger()
    {
        return $this->_onForeignDelete;
    }

    public function setForeignDeletionTrigger($trigger)
    {
        $this->_onForeignDelete = $trigger;
    }

    public function getForeignUpdateTrigger()
    {
        return $this->_onForeignUpdate;
    }

    public function setForeignUpdateTrigger($trigger)
    {
        $this->_onForeignUpdate = $trigger;
    }

    public function getEntity()
    {
        return $this->_entity;
    }

    public function getFieldName()
    {
        return $this->_fieldName;
    }

    public function getFieldFullName()
    {
        return $this->_entity->getEntityFullName() . '.' . $this->_fieldName;
    }

    public function getFieldType()
    {
        return $this->_fieldType;
    }

    public function getFieldTypeWithModifiers()
    {
        return $this->_fieldTypeWithModifiers;
    }

    public function getFilters()
    {
        return $this->_filters;
    }

    public function getFilter($filterName, $default = null)
    {
        return array_try_get($this->_filters, $filterName, $default);
    }

    public function getOwnerFeature()
    {
        return $this->_ownerFeature;
    }

    public function setOwnerFeature($featureName = null)
    {
        if (isset($featureName) && !$this->_isAddedByFeature)
        {
            throw new GrammarException("The field [{$this->getFieldFullName()}] is not added by a feature.");
        }

        $this->_ownerFeature = $featureName;
        
        if (is_null($featureName))
        {
            $this->_isAddedByFeature = false;
        }
    }

    public function isRequired()
    {
        return isset($this->_filters[Type::ATTR_REQUIRED]) &&
            $this->_filters[Type::ATTR_REQUIRED];
    }

    public function setRequired($value = true)
    {
        if ($value)
        {
            $this->_filters[Type::ATTR_REQUIRED] = true;
        }
        else
        {
            unset($this->_filters[Type::ATTR_REQUIRED]);
        }
    }

    public function setEnumerable($value)
    {
        if (is_null($value))
        {
            unset($this->_filters[Type::ATTR_ENUM]);
        }
        else
        {
            $this->_filters[Type::ATTR_ENUM] = new PHPCodingLogic($value);
        }
    }

    public function setState($value)
    {
        if (is_null($value))
        {
            unset($this->_filters[Type::ATTR_STATE]);
        }
        else
        {
            $this->_filters[Type::ATTR_STATE] = new PHPCodingLogic($value);
        }
    }

    public function setIsCalcSum($value = true)
    {
        if ($value)
        {
            $this->_filters[Type::ATTR_NO_INPUT] = true;
        }
        else
        {
            unset($this->_filters[Type::ATTR_NO_INPUT]);
        }
    }

    public function hasInitialValue()
    {
        return array_key_exists(Type::ATTR_INSERT_VALUE, $this->_filters);
    }

    public function getAnyInitialValue()
    {
        return array_try_get(
            $this->_filters,
            Type::ATTR_INSERT_VALUE,
            $this->hasDefaultValueInDbDefinition() ? $this->getDefaultValueInDbDefinition() : null
        );
    }

    public function setDbAutoInsert()
    {
        $this->_filters[Type::ATTR_DB_AUTO_INSERT] = true;
    }

    public function setInitialValue($value)
    {
        if (isset($value))
        {
            $trValue = PHPCodingLogic::translateValue($this, $value);
            if ($trValue instanceof PHPCodingLogic)
            {
                $this->_filters[Type::ATTR_INSERT_VALUE] = $trValue;
                unset($this->_filters[Type::ATTR_DB_AUTO_INSERT]);
                $this->_defaultValueInDbDefinition = null;
            }
            else
            {
                unset($this->_filters[Type::ATTR_INSERT_VALUE]);
                $this->_filters[Type::ATTR_DB_AUTO_INSERT] = true;
                $this->_defaultValueInDbDefinition = $value;
            }
        }
        else
        {
            unset($this->_filters[Type::ATTR_INSERT_VALUE]);
            unset($this->_filters[Type::ATTR_DB_AUTO_INSERT]);
            $this->_defaultValueInDbDefinition = null;
        }
    }

    public function hasDefaultValueInDbDefinition()
    {
        return isset($this->_defaultValueInDbDefinition);
    }

    public function getDefaultValueInDbDefinition()
    {
        return $this->_defaultValueInDbDefinition;
    }

    public function resetFilters()
    {
        $this->_filters = array();
    }

    public function isIndexed()
    {
        return $this->_hasIndex;
    }

    public function setIndexed($value = true)
    {
        $this->_hasIndex = $value;
    }

    public function isForeignKey()
    {
        return $this->_isForeignKey;
    }

    public function setForeignKey()
    {
        $this->_isForeignKey = true;
        $this->setIndexed();
    }

    public function isReadonlyOnCreation()
    {
        return array_try_get($this->_filters, Type::ATTR_READONLY_ON_INSERTING, false);
    }

    public function setReadonlyOnCreation($value = true)
    {
        if ($value)
        {
            $this->_filters[Type::ATTR_READONLY_ON_INSERTING] = true;
        }
        else
        {
            unset($this->_filters[Type::ATTR_READONLY_ON_INSERTING]);
        }
    }

    public function isReadonlyOnUpdating()
    {
        return array_try_get($this->_filters, Type::ATTR_READONLY_ON_UPDATING, false);
    }

    public function setReadonlyOnUpdating($value = true)
    {
        if ($value)
        {
            $this->_filters[Type::ATTR_READONLY_ON_UPDATING] = true;
        }
        else
        {
            unset($this->_filters[Type::ATTR_READONLY_ON_UPDATING]);
        }
    }

    public function isBuiltinType()
    {
        return $this->_isBuiltinType;
    }

    public function getSQLDefinition()
    {
        return $this->_entity->getSchema()->getDbLancer()->getFieldSQLDefinition($this);
    }

    public function getForeignKeyConstraint()
    {
        App::assert($this->_isForeignKey);

        return $this->_entity->getSchema()->getDBLancer()->getForeignConstraintSQLDefinition($this);
    }

    private function _processField($fieldTypeWithModifiers)
    {
        $this->_filters[Type::FIELD_NAME] = new PHPCodingLogic("_META_('{$this->getFullCodeName()}')");

        $typeParts = split_modifiers($fieldTypeWithModifiers);
        $fieldType = trim($typeParts[0]);

        if ($fieldType == '')
        {
            $this->_fieldType = $this->_entity->getSchemaSetName() . '.' . $this->_fieldName;
            $this->_isBuiltinType = false;
        }
        else
        {
            if (in_array($fieldType, Type::getBuiltinTypes()))
            {
                $this->_fieldType = $fieldType;
                $this->_isBuiltinType = true;
            }
            else
            {
                $this->_fieldType = $this->_normalizeFieldType($fieldType);
                $this->_isBuiltinType = false;                
            }
        }

        $this->_filters[Type::ATTR_TYPE] = "{$this->_fieldType}";

        if (is_null($typeParts))
        {
            $typeParts = array();
        }
        
        $typeParts[0] = $this->_fieldType;
        $this->_fieldTypeWithModifiers = merge_modifiers($typeParts);

        array_shift($typeParts);
        if (!empty($typeParts))
        {
            $this->_processFieldTypeModifiers($typeParts);
        }

        if ($this->_isBuiltinType)
        {
            $this->_normalizeBuiltinTypeParams();
        }
    }

    private function _processFieldTypeModifiers(array $modifiers)
    {
        $modifiers = \Bluefin\Lance\TypeModifier::getInstance()->parseModifiers($modifiers);

        foreach ($modifiers as $token => $modifierValue)
        {
            switch ($token)
            {
                case Convention::MODIFIER_TYPE_COMMENT:
                    $this->setComment($modifierValue);
                    break;

                case Convention::MODIFIER_TYPE_LT:
                    $this->_filters[Type::ATTR_MAX_EXCLUSIVE] = $modifierValue;
                    if (array_key_exists(Type::ATTR_MAX, $this->_filters))
                    {
                        throw new GrammarException('The modifiers "<" and "<=" cannot appear at the same time.');
                    }
                    break;

                case Convention::MODIFIER_TYPE_LTE:
                    $this->_filters[Type::ATTR_MAX] = $modifierValue;
                    if (array_key_exists(Type::ATTR_MAX_EXCLUSIVE, $this->_filters))
                    {
                        throw new GrammarException('The modifiers "<" and "<=" cannot appear at the same time.');
                    }
                    break;

                case Convention::MODIFIER_TYPE_GT:
                    $this->_filters[Type::ATTR_MIN_EXCLUSIVE] = $modifierValue;
                    if (array_key_exists(Type::ATTR_MIN, $this->_filters))
                    {
                        throw new GrammarException('The modifiers ">" and ">=" cannot appear at the same time.');
                    }
                    break;

                case Convention::MODIFIER_TYPE_GTE:
                    $this->_filters[Type::ATTR_MIN] = $modifierValue;
                    if (array_key_exists(Type::ATTR_MIN_EXCLUSIVE, $this->_filters))
                    {
                        throw new GrammarException("The modifiers \">\" and \">=\" cannot appear at the same time. Field: {$this->getFieldFullName()}");
                    }
                    break;

                case Convention::MODIFIER_TYPE_DEFAULT:
                    $this->setInitialValue(trim_quote($modifierValue));
                    break;

                case Convention::MODIFIER_TYPE_LENGTH:
                    $this->_filters[Type::ATTR_LENGTH] = (int)$modifierValue;
                    break;

                case Convention::MODIFIER_TYPE_PRECISION:
                    $this->_filters[Type::ATTR_FLOAT_PRECISION] = (int)$modifierValue;
                    break;

                case Convention::MODIFIER_TYPE_NO_INSERT:
                    $this->setReadonlyOnCreation();
                    break;

                case Convention::MODIFIER_TYPE_NO_UPDATE:
                    $this->setReadonlyOnUpdating();
                    break;

                case Convention::MODIFIER_TYPE_POST_PROCESSOR:
                    $this->_filters[Type::ATTR_PRE_COMMIT_FILTER] = PHPCodingLogic::translateValue($this, trim($modifierValue));
                    break;

                case Convention::MODIFIER_TYPE_ON:
                    $this->_referencedFieldName = $modifierValue;
                    break;

                case Convention::MODIFIER_TYPE_ON_DELETE:
                    if ($this->_isBuiltinType)
                    {
                        throw new GrammarException("Foreign key trigger is not allowed for non-foreign field.");
                    }
                    $this->_onForeignDelete = $modifierValue;
                    break;

                case Convention::MODIFIER_TYPE_ON_UPDATE:
                    if ($this->_isBuiltinType)
                    {
                        throw new GrammarException("Foreign key trigger is not allowed for non-foreign field.");
                    }
                    $this->_onForeignUpdate = $modifierValue;
                    break;

                case Convention::MODIFIER_TYPE_NO_INPUT:
                    $this->_filters[Type::ATTR_NO_INPUT] = true;
                    break;

                default:
                    throw new GrammarException("Unknown field type modifier token: {$token}");
            }
        }
    }

    private function _normalizeBuiltinTypeParams()
    {
        //规范配置文件中的值的类型
        if (in_array($this->_fieldType, [Type::TYPE_FLOAT, Type::TYPE_MONEY]))
        {
            if (array_key_exists(Type::ATTR_MIN, $this->_filters))
            {
                $this->_filters[Type::ATTR_MIN] = (float)$this->_filters[Type::ATTR_MIN];
            }
            if (array_key_exists(Type::ATTR_MAX, $this->_filters))
            {
                $this->_filters[Type::ATTR_MAX] = (float)$this->_filters[Type::ATTR_MAX];
            }
            if (array_key_exists(Type::ATTR_MIN_EXCLUSIVE, $this->_filters))
            {
                $this->_filters[Type::ATTR_MIN_EXCLUSIVE] = (float)$this->_filters[Type::ATTR_MIN_EXCLUSIVE];
            }
            if (array_key_exists(Type::ATTR_MAX_EXCLUSIVE, $this->_filters))
            {
                $this->_filters[Type::ATTR_MAX_EXCLUSIVE] = (float)$this->_filters[Type::ATTR_MAX_EXCLUSIVE];
            }

            if ($this->_fieldType == Type::TYPE_MONEY)
            {
                if (!array_key_exists(Type::ATTR_FLOAT_PRECISION, $this->_filters))
                {
                    $this->_filters[Type::ATTR_FLOAT_PRECISION] = Type::TYPE_MONEY_DEFAULT_PRECISION;
                }

                if (array_key_exists(Type::ATTR_MIN, $this->_filters) || array_key_exists(Type::ATTR_MIN_EXCLUSIVE, $this->_filters))
                {
                    $this->_filters[Type::ATTR_MIN] = 0;
                }
            }

            return;
        }

        if (in_array($this->_fieldType, array(Type::TYPE_DATE, Type::TYPE_TIME, Type::TYPE_DATE_TIME, Type::TYPE_TIMESTAMP)))
        {
            // TODO:
            return;
        }

        if (array_key_exists(Type::ATTR_MIN_EXCLUSIVE, $this->_filters))
        {
            $this->_filters[Type::ATTR_MIN] = (int)$this->_filters[Type::ATTR_MIN_EXCLUSIVE] + 1;
            unset($this->_filters[Type::ATTR_MIN_EXCLUSIVE]);
        }

        if (array_key_exists(Type::ATTR_MAX_EXCLUSIVE, $this->_filters))
        {
            $this->_filters[Type::ATTR_MAX] = (int)$this->_filters[Type::ATTR_MAX_EXCLUSIVE] - 1;
            unset($this->_filters[Type::ATTR_MAX_EXCLUSIVE]);
        }

        //TODO:判断配置文件提供的默认值是否符合要求
    }

    private function _normalizeFieldType($fieldType)
    {
        if ('this.' == substr($fieldType, 0, 5))
        {
            return $this->_entity->getSchemaSetName() . '.' . substr($fieldType, 5);
        }
        else if (false === strpos($fieldType, '.'))
        {
            return $this->_entity->getSchemaSetName() . '.' . $fieldType;
        }

        return $fieldType;
    }
}
