<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Lance\Convention;

class Schema
{
    private $_schemaConfig;

    private $_schemaName;
    private $_displayName;
    private $_comment;
    private $_locale;
    private $_namespace;
    private $_db;
    private $_features;
    private $_listedEntities;
    private $_data;

    private $_auth;

    /**
     * @var \Bluefin\Lance\Db\DbLancerInterface
     */
    private $_dbLancer;

    // --------------------------

    private $_schemaNamePascal;
    private $_codeNameMapping;
    private $_modelEntities;
    private $_codeEntities;
    private $_entityPool;
    private $_processingQueue;
    private $_customTypes;

    private $_loaded;

    public function __construct($schemaName, array $schemaConfig)
    {
        $this->_schemaName = $schemaName;
        $this->_schemaConfig = $schemaConfig;

        $this->_comment = $this->_schemaConfig[Convention::KEYWORD_SCHEMA_COMMENT];
        $this->_locale = $this->_schemaConfig[Convention::KEYWORD_SCHEMA_LOCALE];

        $this->_displayName = Convention::buildDisplayName(
            $this->_locale,
            $this->_schemaName,
            $this->_schemaName,
            $this->_comment
        );

        $this->_schemaNamePascal = usw_to_pascal($this->_schemaName);

        $this->_namespace = $this->_schemaConfig[Convention::KEYWORD_SCHEMA_NAMESPACE];
        $this->_db = $this->_schemaConfig[Convention::KEYWORD_SCHEMA_DB];
        $this->_features = array_try_get($this->_schemaConfig, Convention::KEYWORD_SCHEMA_FEATURES, array());
        $this->_listedEntities = $this->_schemaConfig[Convention::KEYWORD_SCHEMA_ENTITIES];
        $this->_auth = array_try_get($this->_schemaConfig, Convention::KEYWORD_SCHEMA_AUTH, array());
        $this->_codeNameMapping = array_flip($this->_listedEntities);

        if (count($this->_listedEntities) != count($this->_codeNameMapping))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Duplicate entity mapping found!");
        }

        $this->_data = $this->_schemaConfig[Convention::KEYWORD_SCHEMA_DATA];

        $dbLancerClass = "\\Bluefin\\Lance\\Db\\" . usw_to_pascal($this->getDbType()) . 'Lancer';
        $this->_dbLancer = new $dbLancerClass($this);

        $this->_entityPool = array();
        $this->_processingQueue = array();
        $this->_customTypes = array();

        $this->_modelEntities = array();
        $this->_codeEntities = array();

        $this->_loaded = false;
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function getLocale()
    {
        return $this->_locale;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getDb()
    {
        return $this->_db;
    }

    public function getDbLancer()
    {
        return $this->_dbLancer;
    }

    public function getDisplayName()
    {
        return $this->_displayName;
    }

    public function getListedEntities()
    {
        return $this->_listedEntities;
    }

    public function getFeatures()
    {
        return $this->_features;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function getSchemaConfig()
    {
        return $this->_schemaConfig;
    }

    public function getSchemaName()
    {
        return $this->_schemaName;
    }

    public function getSchemaNamePascal()
    {
        return $this->_schemaNamePascal;
    }

    public function getDbType()
    {
        return $this->_db[Convention::KEYWORD_SCHEMA_DB_TYPE];
    }

    public function getFeatureConfig($featureName)
    {
        return $this->_features[$featureName];
    }

    public function getAuth()
    {
        return $this->_auth;
    }

    public function getEntityCodeName(Entity $entity)
    {
        return array_try_get($this->_codeNameMapping, $entity->getEntityFullName(), $entity->getEntityName());
    }

    public function getModelEntities()
    {
        return $this->_modelEntities;
    }

    public function getCodeEntities()
    {
        return $this->_codeEntities;
    }

    /**
     * @param $entityName
     * @return Entity
     * @throws \InvalidArgumentException
     */
    public function getLoadedModelEntity($entityName)
    {
        if (!array_key_exists($entityName, $this->_modelEntities))
        {
            throw new \InvalidArgumentException("Entity '{$entityName}' is not found in loaded model entities!");
        }

        /**
         * @var Entity $entity
         */
        $entity = $this->_modelEntities[$entityName];
        App::assert($entity->isReady(), "Model entity '{$entityName}' is not ready!");

        return $entity;
    }

    public function getLoadedCodeEntity($entityName)
    {
        if (!array_key_exists($entityName, $this->_codeEntities))
        {
            throw new \InvalidArgumentException("Entity '{$entityName}' is not found in loaded code entities!");
        }

        /**
         * @var Entity $entity
         */
        $entity = $this->_codeEntities[$entityName];
        App::assert($entity->isReady(), "Code entity '{$entityName}' is not ready!");

        return $entity;
    }

    public function getDatabaseClass()
    {
        return "\\{$this->getNamespace()}\\Model\\{$this->_schemaNamePascal}Database";
    }

    public function getEntityModelClass($entityNamePascal)
    {
        return "\\{$this->getNamespace()}\\Model\\{$this->_schemaNamePascal}\\{$entityNamePascal}";
    }

    public function loadEntities()
    {
        if ($this->_loaded) return;

        foreach ($this->_listedEntities as $entityName => $entityReference)
        {
            $entity = $this->_loadEntity("Schema \"{$this->_schemaName}\"", $entityReference);

            $this->exportEntity($entity);

            if ($entity->getStatus() == Convention::ENTITY_STATUS_INITIAL)
            {
                $entity->continueProcessing();
            }
        }

        $this->processAllRequestsInQueue();

        Arsenal::getInstance()->log()->info(
            "Schema '{$this->getSchemaName()}' is ready!" ,
            Convention::LOG_CAT_LANCE_CORE);

        $this->_loaded = true;
    }

    public function getEntityAsync(Entity $caller, $entityFullName, $state = null, $targetStatus = Convention::ENTITY_STATUS_TO_ADD_REFERENCE)
    {
        // 引用自己
        if ($caller->getEntityFullName() == $entityFullName)
        {
            if ($caller->getStatus() >= $targetStatus)
            {
                $caller->continueProcessing($caller, $state);
                return;
            }
            else
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Invalid self-reference!");
            }
        }

        if (array_key_exists($entityFullName, $this->_entityPool))
        {
            $entity = $this->_entityPool[$entityFullName];
        }
        else
        {
            $entity = $this->_loadEntity("Entity {$caller->getEntityFullName()}", $entityFullName);
            $entity->continueProcessing();
        }

        if ($entity->getStatus() >= $targetStatus)
        {
            $caller->continueProcessing($entity, $state);
        }
        else
        {
            $this->enQueue(new EntityLoadRequest($caller, $entityFullName, $state, $targetStatus));
        }
    }

    /**
     * @param EntityLoadRequest $request
     */
    public function enQueue(EntityLoadRequest $request)
    {
        $this->_processingQueue[] = $request;

        Arsenal::getInstance()->log()->debug(
            "Entity loading request is enqueued. Caller: {$request->callerEntity->getEntityFullName()}, Requested: {$request->requestedEntityFullName}" ,
            Convention::LOG_CAT_LANCE_CORE);
    }

    /**
     * @return EntityLoadRequest
     */
    public function deQueue()
    {
        /**
         * @var EntityLoadRequest $request
         */
        $request = array_pop($this->_processingQueue);

        Arsenal::getInstance()->log()->debug(
            "Entity loading request is dequeued. Caller: {$request->callerEntity->getEntityFullName()}, Requested: {$request->requestedEntityFullName}" ,
            Convention::LOG_CAT_LANCE_CORE);

        return $request;
    }

    /**
     * @return EntityLoadRequest
     */
    public function peekQueue()
    {
        return end($this->_processingQueue);
    }

    /**
     * @return bool
     */
    public function isEmptyQueue()
    {
        return empty($this->_processingQueue);
    }

    public function processAllRequestsInQueue()
    {
        while (!$this->isEmptyQueue())
        {
            $request = $this->deQueue();

            /**
             * @var Entity $dependedEntity
             */
            $dependedEntity = array_try_get($this->_entityPool, $request->requestedEntityFullName);
            App::assert(isset($dependedEntity) && $dependedEntity->isReady());

            $request->callerEntity->continueProcessing($dependedEntity, $request->state);
        }
    }

    public function isCustomType($sourceSite, $type)
    {
        if (array_key_exists($type, $this->_customTypes))
        {
            return true;
        }

        //echo $type . "<br>";

        list($schemaSetName, $typeName) = explode('.', $type);
        $schemaSetName = trim($schemaSetName);
        $typeName = trim($typeName);

        $schemaSetConfig = Arsenal::getInstance()->loadSchemaSet($sourceSite, $schemaSetName);

        $regex = '/^' . Convention::PREFIX_CUSTOM_TYPE . "{$typeName}$/";

        $entry = array_get_by_reg($schemaSetConfig, $regex);

        if (is_null($entry)) return false;

        $this->_customTypes[$type] = $entry[1];

        //echo "found: " . $entry[1] . "<br>";

        return true;
    }

    public function getCustomType($type)
    {
        if (!array_key_exists($type, $this->_customTypes))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("[{$type}] is not a custom type.");
        }

        return $this->_customTypes[$type];
    }

    public function addNewEntity(Entity $entity)
    {
        $fullName = $entity->getEntityFullName();

        App::assert(!array_key_exists($fullName, $this->_entityPool), "Duplicate auto entity: {$fullName}");

        $this->_entityPool[$fullName] = $entity;

        $this->exportEntity($entity);
    }

    public function exportEntity(Entity $entity)
    {
        App::assert(!$entity->isAbstract(),
            "Abstract entity '{$entity->getEntityFullName()}' should not be exported!");

        $entityName = $entity->getCodeName();

        if ($entity->isModelEntity())
        {
            if (array_key_exists($entityName, $this->_modelEntities))
            {
                App::assert($this->_modelEntities[$entityName] === $entity);
            }
            else
            {
                $this->_modelEntities[$entityName] = $entity;
            }
        }
        else
        {
            if (array_key_exists($entityName, $this->_codeEntities))
            {
                App::assert($this->_codeEntities[$entityName] === $entity);
            }
            else
            {
                $this->_codeEntities[$entityName] = $entity;
            }
        }
    }

    /**
     * @param string $sourceSite
     * @param string $entityReference
     * @return Entity
     * @throws Exception\GrammarException
     */
    private function _loadEntity($sourceSite, $entityReference)
    {
        if (array_key_exists($entityReference, $this->_entityPool))
        {
            return $this->_entityPool[$entityReference];
        }

        Arsenal::getInstance()->log()->info(
            "Loading entity '{$entityReference}' from {$sourceSite} ..." ,
            Convention::LOG_CAT_LANCE_CORE);

        list($schemaSetName, $refEntityName) = explode('.', $entityReference, 2);
        $schemaSetName = trim($schemaSetName);
        $refEntityName = trim($refEntityName);

        $schemaSetConfig = Arsenal::getInstance()->loadSchemaSet($sourceSite, $schemaSetName);

        $regex = '/^' . Convention::PATTERN_ENTITY_PREFIX . "?{$refEntityName}$/";

        $entry = array_get_by_reg($schemaSetConfig, $regex);

        if (is_null($entry))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Entity [{$entityReference}] not found! Source: {$sourceSite}");
        }

        list($entityName, $entityConfig, $matches) = $entry;

        $entityType = count($matches) > 1 ? Entity::parseEntityType($matches[1]) : Convention::ENTITY_TYPE_ENTITY;

        $entity = new Entity($this, $schemaSetName, $refEntityName, $entityConfig, $entityType);

        $this->_entityPool[$entityReference] = $entity;

        Arsenal::getInstance()->log()->verbose(
            "The {$entity->getEntityType()} entity '{$entityReference}' is loaded. Status: {$entity->getStatus()}" ,
            Convention::LOG_CAT_LANCE_CORE);

        return $entity;
    }
}
