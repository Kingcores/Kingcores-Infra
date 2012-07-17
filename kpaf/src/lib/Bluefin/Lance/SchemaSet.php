<?php

namespace Bluefin\Lance;

use Bluefin\App;

class SchemaSet
{    
    private $_schemaSetName;
    private $_schemaInfo;

    private $_comment;
    private $_locale;

    private $_db;
    private $_connection;
    private $_namespace;

    private $_features;

    private $_dbmsAdapter;

    private $_schemas;
    private $_entityMapping;
    private $_entities;
    private $_entities2Export;
    private $_outputEntities;

    private $_customTypes;

    private $_data;

    private $_pendingEntities;
    private $_processingQueue;

    public function __construct($schemaSetName)
    {
        $filename = LANCE . "/{$schemaSetName}.yml";
        if (!file_exists($filename))
        {
            throw new \Bluefin\Exception\FileNotFoundException($filename);
        }

        $this->_schemaSetName = $schemaSetName;
        $config = App::loadYmlFileEx($filename);
        $this->_schemaInfo = $config[$this->_schemaSetName];

        $this->_comment = $this->_schemaInfo[Convention::KEYWORD_SCHEMA_COMMENT];
        $this->_locale = $this->_schemaInfo[Convention::KEYWORD_SCHEMA_LOCALE];

        $displayName = Convention::getDisplayName($this->_locale, $this->_schemaSetName, $this->_schemaSetName, $this->_comment);

        $this->_db = $this->_schemaInfo[Convention::KEYWORD_SCHEMA_DB];
        $this->_connection = $this->_db[Convention::KEYWORD_SCHEMA_DB_CONNECTION];
        $this->_namespace = $this->_schemaInfo[Convention::KEYWORD_SCHEMA_NAMESPACE];
        $this->_features = $this->_schemaInfo[Convention::KEYWORD_SCHEMA_FEATURES];
        $this->_data = $this->_schemaInfo[Convention::KEYWORD_SCHEMA_DATA];

        $dbmsAdapterClass = "\\Bluefin\\Lance\\Adapter\\" . usw_to_pascal($this->getDBType()) . 'Adapter';
        $this->_dbmsAdapter = new $dbmsAdapterClass;
    }

    public function getSchemaSetName()
    {
        return $this->_schemaSetName;
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function getLocale()
    {
        return $this->_locale;
    }

    public function getDatabaseClass()
    {
        return "\\{$this->getNamespace()}\\Model\\" . usw_to_pascal($this->getSchemaSetName()) . Convention::SUFFIX_CLASS_DATABASE;
    }

    public function getDB()
    {
        return $this->_db;
    }

    public function getDBAdapterName()
    {
       return $this->_db[Convention::KEYWORD_SCHEMA_DB_ADAPTER];
    }

    public function getDBType()
    {
        return $this->_db[Convention::KEYWORD_SCHEMA_DB_TYPE];
    }

    public function getFeatureConfig($featureName)
    {
        return $this->_features[$featureName];
    }

    /**
     * @return \Bluefin\Lance\Adapter\AdapterInterface
     */
    public function getDBLanceAdapter()
    {
        return $this->_dbmsAdapter;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getRest()
    {
        return _C('rest', array(), $this->_schemaInfo);
    }

    public function getSkeleton()
    {
        return _C('skeleton', array(), $this->_schemaInfo);
    }

    public function getEntityExportName($name)
    {
        if (array_key_exists($name, $this->_entityMapping))
        {
            return $this->_entityMapping[$name];
        }

        $parts = explode('.', $name);
        return $parts[1];
    }

    public function getCustomType($type)
    {
        if (!array_key_exists($type, $this->_customTypes))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("[{$type}] is not a custom type.");
        }

        return $this->_customTypes[$type];
    }

    public function addRelationEntity($schemaName, Entity $entity)
    {
        $entityName = "{$schemaName}.{$entity->getEntityName()}";
        $this->addEntity2Export($entityName);
        return $this->_entities[$entityName] = $entity;
    }

    public function addEntity2Export($entityName)
    {
        array_push_unique($this->_entities2Export, $entityName);
    }

    /**
     * @param Entity $entity
     * @param $requiredEntityName
     * @param $state
     * @param $targetStatus
     */
    public function enQueue(Entity $entity, $requiredEntityName, $state, $targetStatus)
    {
        //echo "Entity {$entity->getEntityName()} is waiting for entity {$requiredEntityName}<br/>";
        $this->_processingQueue[] = array($entity, $requiredEntityName, $state, $targetStatus);
    }

    /**
     * @return array
     */
    public function deQueue()
    {
        return array_pop($this->_processingQueue);
    }

    public function peekQueue()
    {
        return end($this->_processingQueue);
    }

    /**
     * @param  $name
     * @return \Bluefin\Lance\Entity
     */
    public function getUsedEntity($name)
    {
        return array_try_get($this->_outputEntities, $name);
    }

    public function getUsedEntities()
    {
        return $this->_outputEntities;
    }

    public function getField($dotName)
    {
        $parts = explode('.', $dotName);
        App::assert(count($parts) >= 2);

        $fieldName = array_pop($parts);
        $startEntityName = array_shift($parts);

        $entity = $this->getUsedEntity($startEntityName);
        App::assert(isset($entity));

        foreach ($parts as $part)
        {
            $entityName = $entity->getReferencedEntityName($part);
            App::assert(isset($entityName));

            $entityName = $this->getEntityExportName($entityName);

            $entity = $this->getUsedEntity($entityName);
            App::assert(isset($entity));
        }

        return $entity->getField($fieldName);
    }

    public function getEntityAsync(Entity $caller, $entityFullName, $state = null, $targetStatus = Convention::ENTITY_STATUS_TO_ADD_REFERENCE)
    {
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

        if (array_key_exists($entityFullName, $this->_entities))
        {
            $entity = $this->_entities[$entityFullName];
            if ($entity->getStatus() >= $targetStatus)
            {
                $caller->continueProcessing($entity, $state);
                return;
            }
        }

        //echo $entityFullName . "<br/>";
        $this->enQueue($caller, $entityFullName, $state, $targetStatus);
        $entity = $this->_loadEntity($caller->getSchemaName(), $entityFullName);
        $this->_entities[$entityFullName] = $entity;
        $entity->continueProcessing();
        if ($entity->getStatus() >= $targetStatus)
        {
            $caller->continueProcessing($entity, $state);
        }
    }

    /**
     * @throws \Bluefin\Exception\ConfigException
     * @return \Bluefin\Lance\Entity
     */
    public function loadEntities()
    {
        $schemaSetName = $this->_schemaSetName;
        $schemaInfo = $this->_schemaInfo;

        $this->_schemas = array();
        $this->_entities = array();
        $this->_entities2Export = array();
        $this->_entityMapping = array();
        $this->_pendingEntities = array();
        $this->_processingQueue = array();
        $this->_outputEntities = array();
        $this->_customTypes = array();

        $entities = $schemaInfo[Convention::KEYWORD_SCHEMA_ENTITIES];
        if (!$entities)
        {
            throw new \Bluefin\Exception\ConfigException(
                '"' . Convention::KEYWORD_SCHEMA_ENTITIES . "\" is required in schema set \"{$schemaSetName}\"."
            );
        }

        foreach ($entities as $entityName => $entityReference)
        {
            $this->_entityMapping[$entityReference] = $entityName;
            $this->addEntity2Export($entityReference);
            if (!array_key_exists($entityReference, $this->_entities))
            {
                $entity = $this->_loadEntity(null, $entityReference);
                $this->_entities[$entityReference] = $entity;
                $entity->continueProcessing();
            }
        }

        //echo "processing queue...<br/>";

        while (!empty($this->_processingQueue))
        {
            list($entity, $requiredEntityName, $state) = $this->peekQueue();
            App::assert(array_key_exists($requiredEntityName, $this->_entities));

            if ($entity->getStatus() == Convention::ENTITY_STATUS_READY)
            {
                $this->deQueue();
            }
            else
            {
                $entity->continueProcessing($this->_entities[$requiredEntityName], $state);
            }
        }

        foreach ($this->_entities2Export as $entityFullName)
        {
            $entity = $this->_entities[$entityFullName];
            if ($entity->isAbstract()) continue;

            //echo $entityFullName . "<br>";

            $outputName = array_try_get($this->_entityMapping, $entityFullName, $entity->getEntityName());
            if (array_key_exists($outputName, $this->_outputEntities))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Duplicate exported entity name: {$outputName}");
            }

            //echo $outputName . '<br/>';
            $this->_outputEntities[$outputName] = $entity;
        }
    }

    public function isCustomType($type)
    {
        if (array_key_exists($type, $this->_customTypes))
        {
            return true;
        }

        //echo $type . "<br>";

        list($schemaName, $typeName) = explode('.', $type);
        $schemaName = trim($schemaName);
        $typeName = trim($typeName);

        $schemaConfig = $this->_getSchemaConfig($schemaName);

        $regex = '/^' . Convention::PREFIX_CUSTOM_TYPE . "{$typeName}$/";

        $entry = array_get_by_reg($schemaConfig, $regex);

        if (is_null($entry)) return false;

        $this->_customTypes[$type] = $entry[1];

        //echo "found: " . $entry[1] . "<br>";

        return true;
    }

    /**
     * @param $sourceSchemaName
     * @param $entityReference
     * @return Entity
     * @throws Exception\GrammarException
     */
    private function _loadEntity($sourceSchemaName, $entityReference)
    {
        list($schemaName, $refEntityName) = explode('.', $entityReference);
        $schemaName = trim($schemaName);
        $refEntityName = trim($refEntityName);

        $schemaConfig = $this->_getSchemaConfig($schemaName);

        $regex = '/^' . Convention::PREFIX_ENTITY_SET . "?{$refEntityName}$/";

        $entry = array_get_by_reg($schemaConfig, $regex);

        if (is_null($entry))
        {            
            throw new \Bluefin\Lance\Exception\GrammarException("Entity [{$schemaName}.{$refEntityName}] not found." . ($sourceSchemaName ? " Source schema: [{$sourceSchemaName}]." : '') );
        }

        list($entityName, $entityConfig, $matches) = $entry;

        $entityType = count($matches) > 1 ? Entity::parseEntityType($matches[1]) : Convention::ENTITY_TYPE_ENTITY;

        //echo $refEntityName . ': ' . $entityType . "<br>";

        return new Entity($this, $schemaName, $refEntityName, $entityConfig, $entityType);
    }

    private function _getSchemaConfig($schemaName)
    {
        if (array_key_exists($schemaName, $this->_schemas))
        {
            return $this->_schemas[$schemaName];
        }

        $schemaFile = LANCE . '/schema/' . $schemaName . Convention::EXT_DSL_FILE;

        if (!file_exists($schemaFile))
        {
            $schemaFile = BLUEFIN . '/builtin/schema/' . $schemaName . Convention::EXT_DSL_FILE;
        }

        if (!file_exists($schemaFile))
        {
            throw new \Bluefin\Exception\ConfigException("Schema \"{$schemaName}\" not found.");
        }

        return $this->_schemas[$schemaName] = App::loadYmlFileEx($schemaFile);
    }
}
