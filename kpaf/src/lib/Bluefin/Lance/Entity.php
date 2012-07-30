<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Lance\Exception\GrammarException;

use Symfony\Component\Yaml\Yaml;

use Exception;

class Entity
{
    public static function parseEntityType($entityType)
    {
        switch ($entityType)
        {
            case '~':
            case 'abstract`':
                return Convention::ENTITY_TYPE_ABSTRACT;

            case '@':
            case 'enum`':
                return Convention::ENTITY_TYPE_ENUM;

            case '$':
            case 'state`':
                return Convention::ENTITY_TYPE_FST;
        }

        throw new GrammarException("Unknown entity type: {$entityType}");
    }

    private $_schema;

    private $_schemaSetName;
    private $_entityName;

    private $_shortCodeName;
    private $_fullCodeName;

    private $_entityRawFullName;
    private $_entityNamePascal;

    private $_comment;
    private $_displayName;

    private $_entityType;
    private $_entityConfig;
    private $_entityOptions;

    private $_baseEntities;
    private $_mixtures;
    private $_features;

    private $_dslFields;
    private $_fieldObjects;
    private $_memberFields;
    private $_referencedEntityFields;
    private $_childEntityFields;
    private $_fieldsCache;

    private $_primaryKey;
    private $_uniqueKeys;
    private $_foreignKeys;
    private $_alternativeKeys;

    private $_data;

    private $_status;
    private $_pendingCounter;

    private $_m2nRelationships;

    public function __construct(Schema $schema, $schemaSetName, $entityName, array $entityConfig, $entityType)
    {
        $this->_schema = $schema;

        $this->_schemaSetName = $schemaSetName;

        $this->_entityName = $entityName;
        $this->_entityRawFullName = make_dot_name($schemaSetName, $entityName);
        $this->_shortCodeName = $schema->getEntityCodeName($this);
        $this->_fullCodeName = make_dot_name($schema->getSchemaName(), $this->_shortCodeName);
        $this->_entityNamePascal = usw_to_pascal($this->_shortCodeName);

        $this->_entityConfig = $entityConfig;
        $this->_entityType = $entityType;

        $db = $this->_schema->getDb();

        $this->_entityOptions = array(
            Convention::ENTITY_OPTION_DBMS_ENGINE => $db[Convention::KEYWORD_SCHEMA_DB_ENGINE],
            Convention::ENTITY_OPTION_CHARSET => $db[Convention::KEYWORD_SCHEMA_DB_CHARSET],
        );

        $this->_status = Convention::ENTITY_STATUS_INITIAL;

        $this->_comment = array_try_get($entityConfig, Convention::KEYWORD_ENTITY_COMMENT, $this->_entityNamePascal);
        $this->_displayName = Convention::getDisplayName(
            Arsenal::getInstance()->getSchemaSetPragma(
                $schemaSetName,
                Convention::KEYWORD_PRAGMA_COMMENT_LOCALE,
                Convention::DEFAULT_PRAGMA_COMMENT_LOCALE
            ),
            $this->_fullCodeName,
            $this->_comment
        );

        $this->_baseEntities = array();
        $this->_mixtures = array();
        $this->_features = array();

        $this->_dslFields = array();
        $this->_fieldObjects = array();
        $this->_memberFields = array();
        $this->_rawFields = array();
        $this->_referencedEntityFields = array();
        $this->_childEntityFields = array();

        $this->_uniqueKeys = array();
        $this->_alternativeKeys = array();
        $this->_foreignKeys = array();

        $this->_m2nRelationships = array();
    }

    public function isReady()
    {
        return $this->_status == Convention::ENTITY_STATUS_READY;
    }

    public function continueProcessing(Entity $dependedEntity = null, $state = null)
    {
        if ($this->_status == Convention::ENTITY_STATUS_READY)
        {
            Arsenal::getInstance()->log()->info(
                "Entity '{$this->getEntityFullName()}' is ready!" ,
                Convention::LOG_CAT_LANCE_CORE);

            return;
        }

        $continue = false;

        switch ($this->_status)
        {
            case Convention::ENTITY_STATUS_INITIAL:
                Arsenal::getInstance()->log()->verbose(
                    "Initializing {$this->_entityType} entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                switch ($this->_entityType)
                {
                    case Convention::ENTITY_TYPE_ENUM:
                        $continue = $this->_processValuesBlock();
                        break;

                    case Convention::ENTITY_TYPE_FST:
                        $continue = $this->_processStatesBlock();
                        break;

                    default:
                        $continue = $this->_beginInheritanceBlock();
                        break;
                }
                break;

            case Convention::ENTITY_STATUS_INHERIT:
                Arsenal::getInstance()->log()->verbose(
                    "Handling inheritance of entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_endInheritanceBlock($dependedEntity);
                break;

            case Convention::ENTITY_STATUS_TO_MIX:
                Arsenal::getInstance()->log()->verbose(
                    "Handling mixtures of entity '{$this->getEntityFullName()}' (1) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_beginMixtureBlock();
                break;

            case Convention::ENTITY_STATUS_MIX_ONE:
                Arsenal::getInstance()->log()->verbose(
                    "Handling mixtures of entity '{$this->getEntityFullName()}' (2) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_endOneMixture($dependedEntity, $state);
                break;

            case Convention::ENTITY_STATUS_FEATURE1:
                Arsenal::getInstance()->log()->verbose(
                    "Handling features of entity '{$this->getEntityFullName()}' (1) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_processFeatureBlock1Pass();
                break;

            case Convention::ENTITY_STATUS_MEMBER:
                Arsenal::getInstance()->log()->verbose(
                    "Handling members of entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_processMemberBlock();
                break;

            case Convention::ENTITY_STATUS_TO_ADD_REFERENCE:
                Arsenal::getInstance()->log()->verbose(
                    "Handling n:1 references of entity '{$this->getEntityFullName()}' (1) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_beginReferencedRelation();
                break;

            case Convention::ENTITY_STATUS_ADDING_A_REFERENCE:
                /**
                 * @var Reference $reference
                 */
                $reference = $state;

                Arsenal::getInstance()->log()->verbose(
                    "Handling n:1 references of entity '{$this->getEntityFullName()}' on '{$reference->getLocalFieldName()}' (2) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);
                $continue = $this->_endOneReferencedRelation($reference, $dependedEntity);
                break;

            case Convention::ENTITY_STATUS_TO_ADD_M2N:
                Arsenal::getInstance()->log()->verbose(
                    "Handling m:n references of entity '{$this->getEntityFullName()}' (1) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_beginM2NRelation();
                break;

            case Convention::ENTITY_STATUS_ADDING_A_M2N:
                Arsenal::getInstance()->log()->verbose(
                    "Handling m:n references of entity '{$this->getEntityFullName()}' (2) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_endOneM2NRelation($state, $dependedEntity);
                break;

            case Convention::ENTITY_STATUS_FEATURE2:
                Arsenal::getInstance()->log()->verbose(
                    "Handling features of entity '{$this->getEntityFullName()}' (2) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_processFeatureBlock2Pass();
                break;

            case Convention::ENTITY_STATUS_TO_ADD_REFERENCE2:
                Arsenal::getInstance()->log()->verbose(
                    "Handling n:1 references of entity '{$this->getEntityFullName()}' (3) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_beginReferencedRelation();
                break;

            case Convention::ENTITY_STATUS_ADDING_A_REFERENCE2:
                /**
                 * @var Reference $reference
                 */
                $reference = $state;

                Arsenal::getInstance()->log()->verbose(
                    "Handling n:1 references of entity '{$this->getEntityFullName()}' (4) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_endOneReferencedRelation($reference, $dependedEntity);
                break;

            case Convention::ENTITY_STATUS_TO_ADD_M2N2:
                Arsenal::getInstance()->log()->verbose(
                    "Handling m:n references of entity '{$this->getEntityFullName()}' (3) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_beginM2NRelation();
                break;

            case Convention::ENTITY_STATUS_ADDING_A_M2N2:
                Arsenal::getInstance()->log()->verbose(
                    "Handling m:n references of entity '{$this->getEntityFullName()}' (4) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_endOneM2NRelation($state, $dependedEntity);
                break;

            case Convention::ENTITY_STATUS_KEY:
                Arsenal::getInstance()->log()->verbose(
                    "Handling keys of entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_processKeyBlock();
                break;

            case Convention::ENTITY_STATUS_FINAL_CHECK:
                Arsenal::getInstance()->log()->verbose(
                    "Performing final check of entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_finalCheck();
                break;
        }

        if ($continue) $this->continueProcessing();
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function isModelEntity()
    {
        return $this->_entityType == Convention::ENTITY_TYPE_ENTITY;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getSchema()
    {
        return $this->_schema;
    }

    public function getSchemaSetName()
    {
        return $this->_schemaSetName;
    }

    public function getEntityName()
    {
        return $this->_entityName;
    }

    public function getEntityFullName()
    {
        return $this->_entityRawFullName;
    }

    public function getCodeName()
    {
        return $this->_shortCodeName;
    }

    public function getFullCodeName()
    {
        return $this->_fullCodeName;
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function getDisplayName()
    {
        return $this->_displayName;
    }

    public function getEntityType()
    {
        return $this->_entityType;
    }

    public function isAbstract()
    {
        return $this->_entityType == Convention::ENTITY_TYPE_ABSTRACT;
    }

    /**
     * @param $localFieldName
     * @return string;
     */
    public function getReferencedEntityName($localFieldName)
    {
        $fk = $this->getForeignKey($localFieldName);
        return $fk[1];
    }

    public function getEntityOption($option)
    {
        return array_try_get($this->_entityOptions, $option);
    }

    public function setEntityOption($option, $value)
    {
        $this->_entityOptions[$option] = $value;
    }

    public function getBaseEntities()
    {
        return $this->_baseEntities;
    }

    public function hasFeature($featureName)
    {
        return array_key_exists($featureName, $this->_features);
    }

    public function getFeatures()
    {
        return $this->_features;
    }

    public function getFields()
    {
        if (!isset($this->_fieldsCache))
        {
            $this->_fieldsCache = array_get_all($this->_fieldObjects, $this->_memberFields);
        }

        return $this->_fieldsCache;
    }
    
    public function getDslFields()
    {
        return $this->_dslFields;
    }

    public function getMemberFields()
    {
        return $this->_memberFields;
    }

    public function getChildEntityFields()
    {
        return $this->_childEntityFields;
    }

    /**
     * @param  $fieldName
     * @return \Bluefin\Lance\Field
     */
    public function getField($fieldName)
    {
        return array_try_get($this->_fieldObjects, $fieldName);
    }

    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }

    public function setPrimaryKey($fieldName)
    {
        if (is_array($fieldName))
        {
            throw new GrammarException("Combination primary key is not supported by LANCE.");
        }

        /**
         * @var \Bluefin\Lance\Field $field
         */
        $field = $this->getField($fieldName);
        $this->_primaryKey = $field->getFieldName();
        $field->setIndexed();
    }

    public function getForeignKeys()
    {
        return $this->_foreignKeys; 
    }

    public function getForeignKey($localFieldName)
    {
        $fkName = Convention::getForeignKeyName($this->getCodeName(), $localFieldName);
        return array_try_get($this->_foreignKeys, $fkName); 
    }

    public function getUniqueKeys()
    {
        return $this->_uniqueKeys;
    }

    public function getAlternativeKeys()
    {
        return $this->_alternativeKeys;
    }

    public function setKey($name, array $fields, $unique = false)
    {
        if ($unique)
        {
            $this->_uniqueKeys[$name] = $fields;
        }
        else
        {
            $this->_alternativeKeys[$name] = $fields;
        }

        foreach ($fields as $fieldName)
        {
            if (!array_key_exists($fieldName, $this->_fieldObjects))
            {
                throw new GrammarException("Unknown index field: {$fieldName}");
            }

            /**
             * @var \Bluefin\Lance\Field $field
             */
            $field = $this->_fieldObjects[$fieldName];
            $field->setIndexed();
        }
    }

    public function getSQLDefinition()
    {
        return $this->getSchema()->getDbLancer()->getEntitySQLDefinition($this);
    }

    public function getSQLColumns()
    {
        $members = array();
        foreach ($this->_memberFields as $fieldName)
        {
            $members[$fieldName] = $this->_fieldObjects[$fieldName];
        }

        return $members;
    }

    public function getM2NRelationship($targetEntityName)
    {
        return $this->_m2nRelationships[$targetEntityName];
    }

    public function addM2NRelationship($fieldName, $targetEntityName, $relationEntityName, $r2lFieldName, $r2tFieldName)
    {
        $this->_m2nRelationships[$fieldName] =
            array('targetEntity' => $targetEntityName, 'relationEntity' => $relationEntityName, 'localField' => $r2lFieldName, 'targetField' => $r2tFieldName);
    }

    public function addField($fieldNameWithModifiers, $fieldTypeWithModifiers, $inFront = false, $addedByFeature = false)
    {
        $nameParts = split_modifiers($fieldNameWithModifiers);
        $fieldName = trim($nameParts[0]);

        $field = new Field($this, $fieldName, $fieldTypeWithModifiers, $addedByFeature);
        $this->_fieldObjects[$fieldName] = $field;

        $exclusive = false;

        array_shift($nameParts);
        if (!empty($nameParts))
        {
            $modifier = trim($nameParts[0]);

            if ($modifier == Convention::MODIFIER_FIELD_AT_LEAST_ONE_EXCLUSIVE)
            {
                $exclusive = true;
                $field->setRequired(true);
                $isMemberField = false;
            }
            else
            {
                switch ($modifier)
                {
                    case Convention::MODIFIER_FIELD_HAS_ONE:
                        $field->setRequired(true);
                        $isMemberField = true;
                        break;

                    case Convention::MODIFIER_FIELD_NOT_REQUIRED:
                        $field->setRequired(false);
                        $isMemberField = true;
                        break;

                    case Convention::MODIFIER_FIELD_AT_LEAST_ONE:
                        $field->setRequired(true);
                        $isMemberField = false;
                        break;

                    case Convention::MODIFIER_FIELD_HAS_ANY:
                        $field->setRequired(false);
                        $isMemberField = false;
                        break;

                    default:
                        throw new GrammarException("Unknown field name modifier: {$modifier}");
                }
            }
        }
        else
        {
            $field->setRequired(true);
            $isMemberField = true;
        }

        if ($isMemberField)
        {
            if ($field->isBuiltinType())
            {
                if ($inFront)
                {
                    array_unshift($this->_memberFields, $fieldName);
                }
                else
                {
                    $this->_memberFields[] = $fieldName;
                }
            }
            else if ($this->_schema->isCustomType("Entity {$this->getEntityFullName()}", $field->getFieldType()))
            {
                if ($inFront)
                {
                    array_unshift($this->_memberFields, $fieldName);
                }
                else
                {
                    $this->_memberFields[] = $fieldName;
                }

                $baseModifier = $this->_schema->getCustomType($field->getFieldType());
                $parts = split_modifiers($fieldTypeWithModifiers);
                array_shift($parts);
                $newModifiers = $baseModifier . \Bluefin\Convention::DELIMITER_MODIFIER . merge_modifiers($parts);

                $field = new Field($this, $fieldName, $newModifiers, $addedByFeature);
                $this->_fieldObjects[$fieldName] = $field;
            }
            else
            {
                $this->_referencedEntityFields[$fieldName] = new Reference($fieldName, $field->getFieldType(), $field->getReferencedFieldName());
            }
        }
        else
        {
            $this->_childEntityFields[$fieldName] = $field->getFieldType();

            if ($exclusive)
            {
                $field->setOneToManyField();
            }
            else
            {
                $field->setManyToManyField();
            }
        }

        return $field;
    }

    private function _inherit(Entity $baseEntity)
    {
        if (in_array($baseEntity->getEntityType(), array(Convention::ENTITY_TYPE_ENUM, Convention::ENTITY_TYPE_FST)))
        {
            throw new GrammarException("Entity [{$baseEntity->getEntityFullName()}] cannot be inherited.");
        }

        $this->_baseEntities[] = $baseEntity->getEntityFullName();
        $this->_integrate($baseEntity);

        $features = $baseEntity->getFeatures();
        foreach ($features as $featureName => $featureObject)
        {
            /**
             * @var \Bluefin\Lance\Feature\FeatureInterface $featureObject
             */
            $this->_features[$featureName] = $featureObject->cloneFeatureTo($this);
        }

        if (!$baseEntity->getField($baseEntity->getPrimaryKey())->isAddedByFeature())
        {
            $this->setPrimaryKey($baseEntity->getPrimaryKey());
        }

        foreach ($baseEntity->getUniqueKeys() as $key => $fields)
        {
            $this->setKey($key, $fields, true);
        }

        foreach ($baseEntity->getAlternativeKeys() as $key => $fields)
        {
            $this->setKey($key, $fields);
        }
    }

    private function _mix(Entity $baseEntity, array $modifiers = null)
    {
        $this->_mixtures[$baseEntity->getEntityFullName()] = $modifiers;
        $this->_integrate($baseEntity, $modifiers);
    }

    private function _processStatesBlock()
    {
        $this->_data = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_STATES);

        $this->addField(Convention::FIELD_NAME_STATE, Convention::FIELD_TYPE_STATE);
        $this->addField(Convention::FIELD_NAME_COMMENT, Convention::FIELD_TYPE_COMMENT);

        $this->_status = Convention::ENTITY_STATUS_FINAL_CHECK;

        return true;
    }

    private function _processValuesBlock()
    {
        $this->_data = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_VALUES);

        $this->addField(Convention::FIELD_NAME_ENUM_VALUE, Convention::FIELD_TYPE_ENUM_VALUE);
        $this->addField(Convention::FIELD_NAME_DISPLAY_NAME, Convention::FIELD_TYPE_DISPLAY_NAME);

        $this->_status = Convention::ENTITY_STATUS_FINAL_CHECK;

        return true;
    }

    private function _beginInheritanceBlock()
    {
        // is
        $baseEntityName = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_INHERIT);
        
        if (isset($baseEntityName))
        {
            if (!is_string($baseEntityName))
            {
                throw new GrammarException("The base class name should be a string. Entity: {$this->getEntityFullName()}");
            }

            $this->_status++;

            $parts = split_modifiers($baseEntityName);

            $entityFullName = $this->_normalizeEntityName(trim($parts[0]));
            $this->_schema->getEntityAsync($this, $entityFullName, null, Convention::ENTITY_STATUS_READY);
            return false;
        }

        $this->_status += 2;
        return true;
    }

    private function _endInheritanceBlock(Entity $entity)
    {
        $this->_inherit($entity);
        $this->_status++;
        return true;
    }

    private function _beginMixtureBlock()
    {
        // mix
        $mixes = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_MIX);

        if (isset($mixes))
        {
            if (!is_array($mixes))
            {
                throw new GrammarException("The mixture property should be an array. Entity: {$this->getEntityFullName()}");
            }

            $this->_status++;
            $this->_pendingCounter = count($mixes);
            if ($this->_pendingCounter == 0)
            {
                $this->_status++;
                return true;
            }

            foreach ($mixes as $mixtureEntityName)
            {
                $parts = split_modifiers($mixtureEntityName);
                $entityFullName = $this->_normalizeEntityName(trim($parts[0]));
                array_shift($parts);
                $this->_schema->getEntityAsync($this, $entityFullName, $parts, Convention::ENTITY_STATUS_READY);
            }

            return false;
        }

        $this->_status += 2;
        return true;
    }

    private function _endOneMixture(Entity $entity, $modifiers)
    {
        $this->_mix($entity, $modifiers);
        $this->_pendingCounter--;

        if ($this->_pendingCounter == 0)
        {
            $this->_status++;
            return true;
        }

        return false;
    }

    private function _processMemberBlock()
    {
        // has
        $has = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_MEMBER);

        if (isset($has))
        {
            foreach ($has as $fieldNameWithModifiers => $fieldTypeWithModifiers)
            {
                $field = $this->addField($fieldNameWithModifiers, $fieldTypeWithModifiers);
                $this->_dslFields[$fieldNameWithModifiers] = $field->getFieldTypeWithModifiers();
            }
        }

        $this->_status++;
        return true;
    }

    private function _beginReferencedRelation()
    {
        $this->_status++;

        $this->_pendingCounter = count($this->_referencedEntityFields);
        if ($this->_pendingCounter == 0)
        {
            $this->_status++;
            return true;
        }

        foreach ($this->_referencedEntityFields as $ref)
        {
            /**
             * @var \Bluefin\Lance\Reference $ref
             */
            $refEntityName = $ref->getEntityFullName();

            $this->_schema->getEntityAsync($this, $refEntityName, $ref);
        }

        return false;
    }

    private function _endOneReferencedRelation(Reference $ref, Entity $refEntity)
    {
        $fieldName = $ref->getLocalFieldName();
        $refFieldName = $ref->getFieldName();
        isset($refFieldName) || ($refFieldName = $refEntity->getPrimaryKey());
        $refField = $refEntity->getField($refFieldName);

        $this->_memberFields[] = $fieldName;
        $localField = $this->getField($fieldName);

        $refFieldCopy = $refField->getReferenceCopy($this, $fieldName, $fieldName);
        $localComment = $localField->getComment();
        $refFieldCopy->setComment(isset($localComment) ? $localComment : $refEntity->getComment());
        $refFieldCopy->setRequired($localField->isRequired());
        $refFieldCopy->setInitialValue($localField->getAnyInitialValue());
        $refFieldCopy->setUpdateValue($localField->getUpdateValue());
        $refFieldCopy->setReadonlyOnCreation($localField->isReadonlyOnCreation());
        $refFieldCopy->setReadonlyOnUpdating($localField->isReadonlyOnUpdating());

        if ($refEntity->isModelEntity())
        {
            $fkName = Convention::getForeignKeyName($this->getCodeName(), $fieldName);
            $this->_foreignKeys[$fkName] = array($fieldName, $refEntity->getCodeName(), $refFieldName);
            $refFieldCopy->setForeignKey();
        }

        $this->_fieldObjects[$fieldName] = $refFieldCopy;
        $this->_schema->exportEntity($refEntity);

        $this->_pendingCounter--;

        if ($this->_pendingCounter == 0)
        {
            $this->_referencedEntityFields = array();

            $this->_status++;
            return true;
        }

        return false;
    }

    private function _beginM2NRelation()
    {
        $this->_status++;

        $this->_pendingCounter = count($this->_childEntityFields);
        if ($this->_pendingCounter == 0)
        {
            $this->_status++;
            return true;
        }

        foreach ($this->_childEntityFields as $fieldName => $refEntityName)
        {
            $this->_schema->getEntityAsync($this, $refEntityName, $fieldName);
        }

        return false;
    }

    private function _endOneM2NRelation($fieldName, Entity $refEntity)
    {
        $field = $this->getField($fieldName);

        $relationEntityName = $this->getCodeName() . '_' . $refEntity->getCodeName();
        $relationEntityConfig = array(
            Convention::KEYWORD_ENTITY_FEATURE => array(
                Convention::FEATURE_AUTO_UUID,
                Convention::FEATURE_CREATE_TIMESTAMP,
                Convention::FEATURE_CREATED_BY,
            ),
            Convention::KEYWORD_ENTITY_MEMBER => array(
                $this->_shortCodeName => $this->getEntityFullName(),
                $refEntity->getCodeName() => $refEntity->getEntityFullName(),
            )
        );

        if ($field->isOneToManyField())
        {
            $uk = merge_modifiers(array($this->_shortCodeName, Convention::MODIFIER_INDEX_UNIQUE));
            $relationEntityConfig[Convention::KEYWORD_ENTITY_INDEX] = array($uk => $this->_shortCodeName);
        }

        $this->_schema->exportEntity($refEntity);

        $relationEntity = new Entity($this->_schema, $this->_schemaSetName, $relationEntityName, $relationEntityConfig, false);
        $this->_schema->addNewEntity($relationEntity);

        $relationEntity->continueProcessing();

        App::assert($relationEntity->getStatus() == Convention::ENTITY_STATUS_READY);

        $this->addM2NRelationship(
            $fieldName,
            $refEntity->getCodeName(),
            $relationEntityName,
            $this->_shortCodeName,
            $refEntity->getCodeName()
        );

        $this->_pendingCounter--;

        if ($this->_pendingCounter == 0)
        {
            $this->_childEntityFields = array();

            $this->_status++;
            return true;
        }

        return false;
    }

    private function _processFeatureBlock1Pass()
    {
        // features
        $features = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_FEATURE, array());

        // override inherited features
        foreach ($features as $featureWithModifiers)
        {
            $parts = split_modifiers($featureWithModifiers);
            $featureName = trim($parts[0]);
            if ($featureName[0] == '-')
            {
                //取消继承的特性
                $featureName = substr($featureName, 1);
                unset($this->_features[$featureName]);
            }
            else
            {
                array_shift($parts);
                $featureClass = '\\Bluefin\\Lance\\Feature\\' . usw_to_pascal($featureName);

                try
                {
                    $feature = new $featureClass($this, $parts);
                    $this->_features[$featureName] = $feature;
                }
                catch (Exception $e)
                {
                    throw new GrammarException("Invalid feature: {$featureName}. Detail: " . $e->getMessage());
                }
            }
        }

        foreach ($this->_features as $feature)
        {
            /**
             * @var \Bluefin\Lance\Feature\FeatureInterface $feature
             */
            $feature->apply1Pass();
        }

        $this->_status++;
        return true;
    }

    private function _processFeatureBlock2Pass()
    {
        foreach ($this->_features as $feature)
        {
            /**
             * @var \Bluefin\Lance\Feature\FeatureInterface $feature
             */
            $feature->apply2Pass();
        }

        $this->_status++;
        return true;
    }

    private function _processKeyBlock()
    {
        // index
        $index = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_INDEX, array());
        foreach ($index as $indexNameWithModifiers => $indexFields)
        {
            $parts = split_modifiers($indexNameWithModifiers);
            $indexName = trim($parts[0]);
            array_shift($parts);

            $isUnique = true;
            $isPrimary = false;

            if (empty($parts))
            {
                $isUnique = false;
                if ($indexName == 'pk')
                {
                    $isPrimary = true;
                }
            }
            else
            {
                $flag = trim($parts[0]);
                if ($flag == Convention::MODIFIER_INDEX_PRIMARY)
                {
                    $isUnique = false;
                    $isPrimary = true;
                }
                else if ($flag != Convention::MODIFIER_INDEX_UNIQUE)
                {
                    throw new GrammarException("Unknown index modifier: {$indexNameWithModifiers}");
                }
            }

            if (!isset($indexFields) || $indexFields === '')
            {
                $indexFields = $isPrimary ? $indexName : array($indexName);
            }
            else if (!is_array($indexFields))
            {
                $indexFields = $isPrimary ? $indexFields : array($indexFields);
            }

            if ($isPrimary)
            {
                $this->setPrimaryKey($indexFields);
            }
            else
            {
                $this->setKey(
                    $isUnique
                            ? Convention::PREFIX_UNIQUE_KEY . combine_usw($this->getCodeName(), $indexName)
                            : Convention::PREFIX_NORMAL_KEY . combine_usw($this->getCodeName(), $indexName),
                    $indexFields,
                    $isUnique
                );
            }
        }

        $this->_status++;
        return true;
    }

    private function _integrate(Entity $baseEntity, array $modifiers = null)
    {
        $rawFields = $baseEntity->getDslFields();
        $prefix = null;
        $suffix = null;
        $nonRequired = false;
        $exclude = array();

        if (isset($modifiers))
        {
            foreach ($modifiers as $modifier)
            {
                $modifier = trim($modifier);
                switch ($modifier[0])
                {
                    case Convention::MODIFIER_MIXTURE_PREFIX:
                        $prefix = trim(substr($modifier, 1));
                        if ($prefix == '') $prefix = $baseEntity->getCodeName();
                        break;

                    case Convention::MODIFIER_MIXTURE_SUFFIX:
                        $suffix = trim(substr($modifier, 1));
                        if ($suffix == '') $suffix = $this->_shortCodeName;
                        break;

                    case Convention::MODIFIER_MIXTURE_NON_REQUIRED:
                        $nonRequired = true;
                        break;

                    case Convention::MODIFIER_MIXTURE_EXCLUDE:
                        $exclude[] = trim(substr($modifier, 1));
                        break;

                    default:
                        throw new GrammarException("Unknown mixture modifier: {$modifier}");
                }
            }
        }

        foreach ($rawFields as $fieldName => $fieldType)
        {
            $nameParts = split_modifiers($fieldName);

            if (in_array($nameParts[0], $exclude))
            {
                continue;
            }

            if (isset($prefix)) $nameParts[0] = $prefix . '_' . $nameParts[0];
            if (isset($suffix)) $nameParts[0] = $nameParts[0] . $suffix . '_';

            $field = $this->addField(merge_modifiers($nameParts), $fieldType);
            $this->_dslFields[$fieldName] = $field->getFieldTypeWithModifiers();
            if ($nonRequired) $field->setRequired(false);
        }
    }

    private function _finalCheck()
    {
        if (empty($this->_memberFields))
        {
            throw new GrammarException(
                "No member fields defined for [{$this->_entityName}]."
            );
        }

        if (!isset($this->_primaryKey))
        {
            $this->setPrimaryKey($this->_memberFields[0]);
        }

        $this->_status++;
        return true;
    }

    private function _normalizeEntityName($entityName)
    {
        if ('this.' == substr($entityName, 0, 5))
        {
            return $this->_schemaSetName . '.' . substr($entityName, 5);
        }
        else if (false === strpos($entityName, '.'))
        {
            return $this->_schemaSetName . '.' . $entityName;
        }

        return $entityName;
    }
}
