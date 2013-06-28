<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Data\Model;
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

            case '&':
            case 'relation`':
                return Convention::ENTITY_TYPE_RELATION;
        }

        throw new GrammarException("Unknown entity type: {$entityType}");
    }

    private $_schema;

    private $_schemaSetName;
    private $_entityName;

    private $_shortCodeName;
    private $_fullCodeName;

    private $_entityRawFullName;
    private $_codeNamePascal;

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
    private $_fieldsCache;
    private $_relationFields;

    private $_primaryKey;
    private $_uniqueKeys;
    private $_foreignKeys;
    private $_alternativeKeys;
    private $_relationKey;

    private $_phpTriggers;

    private $_data;

    private $_status;
    private $_pendingCounter;

    private $_commentLocale;

    private $_enumDefaultValue;

    private $_fsm;
    private $_hasActionApi;
    private $_fsActions;
    private $_ownerFieldName;
    private $_creatorFieldName;
    private $_stateField;
    private $_service;


    private $_m2nRelations;

    /**
     * @var Entity
     */
    private $_stateEntity;

    public function __construct(Schema $schema, $schemaSetName, $entityName, array $entityConfig, $entityType)
    {
        $this->_schema = $schema;

        $this->_schemaSetName = $schemaSetName;

        $this->_entityName = $entityName;
        $this->_entityRawFullName = make_dot_name($schemaSetName, $entityName);
        $this->_shortCodeName = $schema->getEntityCodeName($this);
        $this->_fullCodeName = make_dot_name($schema->getSchemaName(), $this->_shortCodeName);
        $this->_codeNamePascal = usw_to_pascal($this->_shortCodeName);

        $this->_entityConfig = $entityConfig;
        $this->_entityType = $entityType;

        $db = $this->_schema->getDb();

        $this->_entityOptions = array(
            Convention::ENTITY_OPTION_DBMS_ENGINE => $db[Convention::KEYWORD_SCHEMA_DB_ENGINE],
            Convention::ENTITY_OPTION_CHARSET => $db[Convention::KEYWORD_SCHEMA_DB_CHARSET],
        );

        $this->_status = Convention::ENTITY_STATUS_INITIAL;

        $this->_commentLocale = Arsenal::getInstance()->getSchemaSetPragma(
            $schemaSetName,
            Convention::KEYWORD_PRAGMA_COMMENT_LOCALE,
            Convention::DEFAULT_PRAGMA_COMMENT_LOCALE
        );

        $this->_comment = array_try_get($entityConfig, Convention::KEYWORD_ENTITY_COMMENT, $this->_codeNamePascal);
        $this->_displayName = Convention::buildDisplayName(
            $this->_commentLocale,
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

        $this->_uniqueKeys = array();
        $this->_alternativeKeys = array();
        $this->_foreignKeys = array();
        $this->_relationKey = array();

        $this->_m2nRelations = [];

        $this->_service = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_SERVICE);
        $this->_hasActionApi = false;
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

                if ($this->_entityType == Convention::ENTITY_TYPE_RELATION)
                {
                    $this->_processRelationBlock();
                }

                $continue = $this->_processMemberBlock();
                break;

            case Convention::ENTITY_STATUS_TO_ADD_REFERENCE:
                Arsenal::getInstance()->log()->verbose(
                    "Handling n:1 references of entity '{$this->getEntityFullName()}' (1) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_beginReferencedRelation();
                break;

            case Convention::ENTITY_STATUS_ADDING_A_REFERENCE:

                $reference = $state;
                /**
                 * @var \Bluefin\Lance\Reference $reference
                 */

                Arsenal::getInstance()->log()->verbose(
                    "Handling n:1 references of entity '{$this->getEntityFullName()}' on '{$reference->getLocalFieldName()}' (2) ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);
                $continue = $this->_endOneReferencedRelation($reference, $dependedEntity);
                break;

            case Convention::ENTITY_STATUS_KEY:
                Arsenal::getInstance()->log()->verbose(
                    "Handling keys of entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                $continue = $this->_processKeyBlock();
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

            case Convention::ENTITY_STATUS_FINAL_CHECK:
                Arsenal::getInstance()->log()->verbose(
                    "Performing final check of entity '{$this->getEntityFullName()}' ..." ,
                    Convention::LOG_CAT_LANCE_DIAG);

                // add m2n relation
                if ($this->_entityType == Convention::ENTITY_TYPE_RELATION)
                {
                    $fieldName1 = $this->_relationFields[0];
                    $fieldName2 = $this->_relationFields[1];

                    $refInfo1 = $this->getForeignKey($fieldName1);
                    $refInfo2 = $this->getForeignKey($fieldName2);

                    $refEntityName1 = $refInfo1[1];
                    $refEntityName2 = $refInfo2[1];

                    $refEntity1 = $this->_schema->getLoadedModelEntity($refEntityName1);
                    $modelEntities = $this->_schema->getModelEntities();
                    $refEntity2 = array_try_get($modelEntities, $refEntityName2);

                    if (isset($refEntity2))
                    {
                        $refEntity1->setM2NRelation($this->getCodeName(), $refInfo1[2], $fieldName1, $fieldName2, in_array($fieldName1, $this->_relationKey), $refEntityName2, $refInfo2[2]);
                        $refEntity2->setM2NRelation($this->getCodeName(), $refInfo2[2], $fieldName2, $fieldName1, in_array($fieldName2, $this->_relationKey), $refEntityName1, $refInfo1[2]);
                    }
                    else
                    {
                        $refEntity1->setM2NRelation($this->getCodeName(), $refInfo1[2], $refInfo1[0], in_array($fieldName1, $this->_relationKey));
                    }
                }

                // load triggers
                $phpTriggers = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_PHP_TRIGGERS);
                if (!empty($phpTriggers))
                {
                    if (!empty($this->_phpTriggers))
                    {
                        $this->_phpTriggers = array_merge($this->_phpTriggers, $phpTriggers);
                    }
                    else
                    {
                        $this->_phpTriggers = $phpTriggers;
                    }
                }

                // load fsm
                $this->_fsm = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_FSM);

                if (!empty($this->_fsm))
                {
                    $this->_fsActions = [];

                    if (!isset($this->_stateField))
                    {
                        throw new GrammarException("This entity has no state field. Entity: {$this->getEntityFullName()}");
                    }

                    $statusStates = $this->_stateEntity->getData();

                    foreach ($this->_fsm as $state => &$context)
                    {
                        if (!array_key_exists($state, $statusStates))
                        {
                            throw new GrammarException("'{$state}' is not a valid state of '{$this->_stateEntity->getEntityFullName()}'.' Entity: {$this->getEntityFullName()}");
                        }

                        if (empty($context)) continue;

                        if (array_key_exists(Convention::KEYWORD_ENTITY_FSM_TRANSITIONS, $context))
                        {
                            $transitions = &$context[Convention::KEYWORD_ENTITY_FSM_TRANSITIONS];

                            foreach ($transitions as $actionName => &$conditions)
                            {
                                if (array_key_exists($actionName, $this->_fsActions))
                                {
                                    $this->_fsActions[$actionName][] = $state;
                                }
                                else
                                {
                                    $this->_fsActions[$actionName] = [ $state ];
                                }

                                if (!array_key_exists(Convention::KEYWORD_ENTITY_FSM_TARGET, $conditions))
                                {
                                    throw new GrammarException("'TARGET' configuration is required for the '{$actionName}' action on state '{$state}'. Entity: {$this->getEntityFullName()}");
                                }

                                $target = $conditions[Convention::KEYWORD_ENTITY_FSM_TARGET];

                                if (!array_key_exists($target, $statusStates))
                                {
                                    throw new GrammarException("Target state '{$target}' is not a valid state of '{$this->_stateEntity->getEntityFullName()}'. Entity: {$this->getEntityFullName()}");
                                }

                                if (!array_key_exists(Convention::KEYWORD_ENTITY_FSM_ALLOWED_ROLES, $conditions))
                                {
                                    throw new GrammarException("'ROLES' configuration is required for the transition '{$state}' -> '{$target}'. Entity: {$this->getEntityFullName()}");
                                }

                                $this->_normalizeRoles($conditions);
                                $conditions[Convention::KEYWORD_ENTITY_FSM_FROM] = $state;

                                if (array_key_exists(Convention::KEYWORD_ENTITY_FSM_INPUT, $conditions))
                                {
                                    $fieldSetters = [];
                                    $normalizedInputs = [];
                                    $inputs = $conditions[Convention::KEYWORD_ENTITY_FSM_INPUT];

                                    foreach ($inputs as $param)
                                    {
                                        $isField = false;

                                        if ($param[0] == '@')
                                        {
                                            $isField = true;
                                            $param = mb_substr($param, 1);
                                        }

                                        $parts = split_modifiers($param);
                                        $param = array_shift($parts);
                                        $argInfo = [];
                                        $required = true;

                                        if (!empty($parts))
                                        {
                                            foreach ($parts as $part)
                                            {
                                                if ($part == '?')
                                                {
                                                    $required = false;
                                                }
                                            }
                                        }

                                        if ($isField && !array_key_exists($param, $this->_fieldObjects))
                                        {
                                            throw new GrammarException("Field '{$param}' is not a member of entity '{$this->getEntityFullName()}'.");
                                        }

                                        $argInfo['required'] = $required;

                                        if ($isField)
                                        {
                                            $fieldSetters[$param] = $required;
                                        }
                                        else
                                        {
                                            $normalizedInputs[$param] = $argInfo;
                                        }
                                    }

                                    $conditions[Convention::KEYWORD_ENTITY_FSM_INPUT] = $normalizedInputs;
                                    $conditions[Convention::KEYWORD_ENTITY_FSM_UPDATE_FILEDS] = $fieldSetters;
                                }

                                if (!$this->_hasActionApi &&
                                    array_key_exists(Convention::KEYWORD_ENTITY_FSM_CALLER, $conditions) &&
                                    in_array('service', $conditions[Convention::KEYWORD_ENTITY_FSM_CALLER]))
                                {
                                    $this->_hasActionApi = true;
                                }
                                //$this->_actions[$actionName] = $conditions;
                            }
                        }
                    }
                }

                if (!empty($this->_service))
                {
                    foreach ($this->_service as &$context)
                    {
                        $this->_normalizeRoles($context);
                    }
                }

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
        return $this->_entityType == Convention::ENTITY_TYPE_ENTITY || $this->_entityType == Convention::ENTITY_TYPE_RELATION;
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

    public function getCodeNamePascal()
    {
        return $this->_codeNamePascal;
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

    public function getCommentLocale()
    {
        return $this->_commentLocale;
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

    public function getEnumDefaultValue()
    {
        return $this->_enumDefaultValue;
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

    public function getFeature($featureName)
    {
        return array_try_get($this->_features, $featureName);
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

    /**
     * @param  $fieldName
     * @return \Bluefin\Lance\Field
     */
    public function getField($fieldName)
    {
        return array_try_get($this->_fieldObjects, $fieldName);
    }

    public function getStateField()
    {
        return $this->_stateField;
    }

    public function getStateEntity()
    {
        return $this->_stateEntity;
    }

    public function getFST()
    {
        return $this->_fsm;
    }

    public function hasActionApi()
    {
        return $this->_hasActionApi;
    }

    public function getStateActions()
    {
        return $this->_fsActions;
    }

    public function getService()
    {
        return $this->_service;
    }

    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }

    public function getOwnerField()
    {
        return $this->_ownerFieldName;
    }

    public function setOwnerField($fieldName)
    {
        if (!array_key_exists($fieldName, $this->_fieldObjects))
        {
            throw new GrammarException("Invalid owner field name {$fieldName}");
        }

        $this->_ownerFieldName = $fieldName;
    }

    public function getCreatorField()
    {
        return $this->_creatorFieldName;
    }

    public function setCreatorField($fieldName)
    {
        if (!array_key_exists($fieldName, $this->_fieldObjects))
        {
            throw new GrammarException("Invalid owner field name {$fieldName}");
        }

        $this->_creatorFieldName = $fieldName;
    }

    public function setPrimaryKey($fieldName)
    {
        if (is_array($fieldName))
        {
            throw new GrammarException("Combination primary key is not supported by LANCE.");
        }

        if (!empty($this->_primaryKey))
        {
            if ($this->_primaryKey == $fieldName) return;

            throw new GrammarException("Primary has already been set.");
        }

        /**
         * @var \Bluefin\Lance\Field $field
         */
        $field = $this->getField($fieldName);

        if (!isset($field))
        {
            throw new GrammarException("'{$fieldName}' is not a field of entity '{$this->getEntityFullName()}'.");
        }

        $this->_primaryKey = $field->getFieldName();
        $field->setIndexed();
    }

    public function getM2NRelations()
    {
        return $this->_m2nRelations;
    }

    public function setM2NRelation($relationEntityName, $localFieldName, $relationFieldName, $relationField2Name, $unique = false, $toEntityName = null, $toFieldName = null)
    {
        if (array_key_exists($relationEntityName, $this->_fieldObjects))
        {
            throw new GrammarException("Name of relation entity '{$relationEntityName}' is duplicate with a field in entity '{$this->getEntityFullName()}'.");
        }

        if (isset($toEntityName))
        {
            $this->_m2nRelations[$relationEntityName] = [ $localFieldName, $relationFieldName, $unique, $relationField2Name, $toEntityName, $toFieldName ];
        }
        else
        {
            $this->_m2nRelations[$relationEntityName] = [ $localFieldName, $relationFieldName, $unique, $relationField2Name ];
        }
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
            if (array_key_exists($name, $this->_uniqueKeys))
            {
                throw new GrammarException("Duplicate unique key: {$name}");
            }
            $this->_uniqueKeys[$name] = $fields;
        }
        else
        {
            if (array_key_exists($name, $this->_alternativeKeys))
            {
                throw new GrammarException("Duplicate alternative key: {$name}");
            }
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

    public function getPHPTriggers()
    {
        return $this->_phpTriggers;
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

    public function addField($fieldNameWithModifiers, $fieldTypeWithModifiers, $inFront = false, $addedByFeature = false)
    {
        $nameParts = split_modifiers($fieldNameWithModifiers);
        $fieldName = trim($nameParts[0]);

        $field = new Field($this, $fieldName, $fieldTypeWithModifiers, $addedByFeature);

        if (array_key_exists($fieldName, $this->_fieldObjects))
        {
            throw new GrammarException('Duplicate field name. Entity: ' . $this->getEntityFullName() . ', field: ' . $fieldName);
        }

        $this->_fieldObjects[$fieldName] = $field;

        array_shift($nameParts);
        if (!empty($nameParts))
        {
            $modifier = trim($nameParts[0]);

            if ($this->_entityType == Convention::ENTITY_TYPE_RELATION)
            {
                $field->setRequired(true);

                if ($modifier == Convention::MODIFIER_FIELD_ONLY_ONE)
                {
                    $this->_relationKey[] = $fieldName;
                }
                else
                {
                    throw new GrammarException("Unknown field name modifier: {$modifier}");
                }
            }
            else
            {
                if ($modifier == Convention::MODIFIER_FIELD_NOT_REQUIRED)
                {
                    $field->setRequired(false);
                }
                else
                {
                    throw new GrammarException("Unknown field name modifier: {$modifier}");
                }
            }
        }
        else
        {
            $field->setRequired(true);
        }

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
            $newModifiers = $baseModifier;

            if (!empty($parts))
            {
                $newModifiers .= \Bluefin\Convention::DELIMITER_MODIFIER . merge_modifiers($parts);
            }

            $field = new Field($this, $fieldName, $newModifiers, $addedByFeature);
            $this->_fieldObjects[$fieldName] = $field;
        }
        else
        {
            $this->_referencedEntityFields[$fieldName] = new Reference($fieldName, $field->getFieldType(), $field->getReferencedFieldName());
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

        $this->_phpTriggers = $baseEntity->getPHPTriggers();
    }

    private function _mix(Entity $baseEntity, array $modifiers = null)
    {
        $this->_mixtures[$baseEntity->getEntityFullName()] = $modifiers;
        $this->_integrate($baseEntity, $modifiers);
    }

    private function _processStatesBlock()
    {
        $this->_data = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_STATES);
        $this->_enumDefaultValue = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_DEFAULT_VALUE, array_get_first_key($this->_data));

        if (!array_key_exists($this->_enumDefaultValue, $this->_data))
        {
            throw new GrammarException("Invalid default value. Entity: {$this->getEntityFullName()}");
        }

        foreach ($this->_data as $state => $comment)
        {
            if (Convention::isKeyword($state))
            {
                throw new \Bluefin\Exception\ConfigException(
                    "'{$state}' is a PHP keyword and cannot be used as a state of {$this->_fullCodeName}"
                );
            }
            Convention::addMetadataTranslation($this->_commentLocale, "{$this->_fullCodeName}.{$state}", $comment);
        }

        $this->addField(Convention::FIELD_NAME_STATE, Convention::FIELD_TYPE_STATE);
        $this->addField(Convention::FIELD_NAME_COMMENT, Convention::FIELD_TYPE_COMMENT);

        $this->_status = Convention::ENTITY_STATUS_FINAL_CHECK;

        return true;
    }

    private function _processValuesBlock()
    {
        $this->_data = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_VALUES);
        $this->_enumDefaultValue = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_DEFAULT_VALUE, array_get_first_key($this->_data));

        if (!array_key_exists($this->_enumDefaultValue, $this->_data))
        {
            throw new GrammarException("Invalid default value. Entity: {$this->getEntityFullName()}");
        }

        foreach ($this->_data as $value => $displayName)
        {
            if (Convention::isKeyword($value))
            {
                throw new \Bluefin\Exception\ConfigException(
                    "'{$value}' is a PHP keyword and cannot be used as a value of {$this->_fullCodeName}"
                );
            }

            Convention::addMetadataTranslation($this->_commentLocale, "{$this->_fullCodeName}.{$value}", $displayName);
        }

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

    private function _processRelationBlock()
    {
        // relation
        $relation = array_try_get($this->_entityConfig, Convention::KEYWORD_ENTITY_RELATION);

        if (isset($relation))
        {
            if (count($relation) != 2)
            {
                throw new GrammarException("Invalid 'between' block! It should include 2 fields.");
            }

            $this->_relationFields = array_keys($relation);

            foreach ($relation as $fieldNameWithModifiers => $fieldTypeWithModifiers)
            {
                $field = $this->addField($fieldNameWithModifiers, $fieldTypeWithModifiers);
                $this->_dslFields[$fieldNameWithModifiers] = $field->getFieldTypeWithModifiers();
            }
        }
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

        if (empty($refFieldName))
        {
            throw new GrammarException("Primary key is not found in \"{$refEntity->getEntityFullName()}\".");
        }

        $refField = $refEntity->getField($refFieldName);

        $this->_memberFields[] = $fieldName;
        $localField = $this->getField($fieldName);

        $refFieldCopy = $refField->getReferenceCopy($this, $fieldName, $fieldName);
        $localComment = $localField->getComment();

        $refFieldCopy->setComment(isset($localComment) ? $localComment : $refEntity->getComment());
        $refFieldCopy->setRequired($localField->isRequired());
        $refFieldCopy->setInitialValue($localField->getAnyInitialValue());
        $refFieldCopy->setReadonlyOnCreation($localField->isReadonlyOnCreation());
        $refFieldCopy->setReadonlyOnUpdating($localField->isReadonlyOnUpdating());
        $refFieldCopy->setForeignDeletionTrigger($localField->getForeignDeletionTrigger());
        $refFieldCopy->setForeignUpdateTrigger($localField->getForeignUpdateTrigger());

        if ($refEntity->isModelEntity())
        {
            $fkName = Convention::getForeignKeyName($this->getCodeName(), $fieldName);
            $this->_foreignKeys[$fkName] = array($fieldName, $refEntity->getCodeName(), $refFieldName);
            $refFieldCopy->setForeignKey();
        }
        else
        {
            if ($this->_entityType == Convention::ENTITY_TYPE_RELATION)
            {
                if ($this->_relationFields[0] == $fieldName)
                {
                    throw new GrammarException("First relation field cannot reference to a non-model entity. Please try to put it in the second place. Entity: {$this->getEntityFullName()}");
                }
            }

            if ($refEntity->getEntityType() == Convention::ENTITY_TYPE_ENUM)
            {
                $refFieldCopy->setEnumerable("new {$refEntity->getCodeNamePascal()}()");
            }
            else if ($refEntity->getEntityType() == Convention::ENTITY_TYPE_FST)
            {
                if (isset($this->_stateField))
                {
                    throw new GrammarException("Only one state field is allowed for an entity. Entity: {$this->getEntityFullName()}");
                }

                $this->_stateField = $fieldName;
                $this->_stateEntity = $refEntity;

                $refFieldCopy->setState("new {$refEntity->getCodeNamePascal()}()");

                $states = $refEntity->getData();

                //添加状态的时间
                foreach ($states as $state => $displayName)
                {
                    $stateTimeFieldName = $state . \Bluefin\Convention::STATE_CHANGED_TIME_SUFFIX;

                    $fieldModifier = [];
                    $fieldModifier[] = \Bluefin\Data\Type::TYPE_DATE_TIME;
                    $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . Convention::buildDisplayName($this->getCommentLocale(), make_dot_name($this->getFullCodeName(), $stateTimeFieldName), $displayName . _T('time', 'dict'));

                    $field = $this->addField($stateTimeFieldName, merge_modifiers($fieldModifier), false, true);
                    $field->setRequired(false);
                }

                //添加状态的历史
                $stateLogFieldName = $fieldName . \Bluefin\Convention::STATE_CHANGED_HISTORY_SUFFIX;

                $fieldModifier = [];
                $fieldModifier[] = \Bluefin\Data\Type::TYPE_TEXT;
                $fieldModifier[] = Convention::MODIFIER_TYPE_LTE . '1000';
                $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . Convention::buildDisplayName($this->getCommentLocale(), make_dot_name($this->getFullCodeName(), $stateLogFieldName), $refFieldCopy->getDisplayName() . _T('history', 'dict'));

                $field = $this->addField($stateLogFieldName, merge_modifiers($fieldModifier), false, true);
                $field->setRequired(false);
                $field->setInitialValue(new PHPCodingLogic(str_quote($refEntity->getEnumDefaultValue(), true)));
            }

            if (!($refFieldCopy->hasInitialValue() || $refFieldCopy->hasDefaultValueInDbDefinition()))
            {
                $refFieldCopy->setInitialValue($refEntity->getEnumDefaultValue());
            }
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
        foreach ($this->_relationKey as $key)
        {
            $this->setKey(
                Convention::PREFIX_UNIQUE_KEY . $key,
                [ $key ],
                true
            );
        }

        if ($this->_entityType == Convention::ENTITY_TYPE_RELATION)
        {
            if (empty($this->_relationFields))
            {
                throw new GrammarException("Missing 'between' block!");
            }

            $this->setKey(
                Convention::PREFIX_UNIQUE_KEY . $this->getCodeName(),
                $this->_relationFields,
                true
            );
        }

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

    private function _normalizeRoles(array &$conditions)
    {
        if (array_key_exists(Convention::KEYWORD_ENTITY_FSM_ALLOWED_ROLES, $conditions))
        {
            $roles = &$conditions[Convention::KEYWORD_ENTITY_FSM_ALLOWED_ROLES];
            if ($roles == '*all*') return;

            $specificRoles = false;

            foreach ($roles as $auth => $authRoles)
            {
                if (empty($authRoles))
                {
                    unset($roles[$auth]);
                    continue;
                }

                if (is_int($auth))
                {
                    throw new GrammarException("Invalid ROLES definition! Syntax: <authName> => <array of roles>");
                }

                is_array($authRoles) || ($authRoles = [$authRoles]);

                if (in_array(Convention::KEYWORD_ENTITY_FSM_ROLE_OWNER_TOKEN, $authRoles))
                {
                    if (!$this->hasFeature(Convention::FEATURE_OWNER_FIELD))
                    {
                        throw new GrammarException("This entity has no owner field but has *owner* restriction in FST table. Entity: {$this->getEntityFullName()}");
                    }

                    $specificRoles = true;
                }

                if (in_array(Convention::KEYWORD_ENTITY_FSM_ROLE_CREATOR_TOKEN, $authRoles))
                {
                    if (!$this->hasFeature(Convention::FEATURE_CREATOR_FIELD))
                    {
                        throw new GrammarException("This entity has no creator field but has *creator* restriction in FST table. Entity: {$this->getEntityFullName()}");
                    }

                    $specificRoles = true;
                }
            }

            $conditions[Convention::KEYWORD_ENTITY_FSM_SPECIFIC_ROLES] = $specificRoles;
        }
    }
}
