<?php
namespace Bluefin\Data;

use Bluefin\Data\Database;

class ModelMetadata
{
    /**
     * @var \Bluefin\Data\Database
     */
    private $_modelName;
    private $_pkName;
    private $_uks;
    private $_fieldNames;
    private $_fieldOptions;
    private $_features;
    private $_relations;

    public function __construct(Database $db, $modelName, array $uks, array $fieldOptions, array $features, array $relations)
    {
        $this->_db = $db;
        $this->_modelName = $modelName;
        $this->_uks = $uks;
        $this->_fieldOptions = $fieldOptions;
        $this->_features = $features;
        $this->_relations = $relations;

        $this->_fieldNames = array_keys($fieldOptions);
        $this->_pkName = $this->_fieldNames[0];
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->_modelName;
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
    public function getUniqueKeys()
    {
        return $this->_uks;
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
     * @return array
     */
    public function getModelFeatures()
    {
        return $this->_features;
    }

    public function hasFeature($featureName)
    {
        return array_key_exists($featureName, $this->_features);
    }

    public function getRelation($fieldName)
    {
        if (array_key_exists($fieldName, $this->_relations))
        {
            return $this->_relations[$fieldName];
        }

        //$fieldName = $this->getFieldNameByRef($fieldName);
        return array_try_get($this->_relations, $fieldName);
    }

    public function hasRelationship($relationName)
    {
        return in_array($relationName, $this->_relations);
    }
}
