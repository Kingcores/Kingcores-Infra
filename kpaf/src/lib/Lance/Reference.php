<?php

namespace Bluefin\Lance;

class Reference
{
    private $_localFieldName;
    private $_entityName;
    private $_fieldName;

    public function __construct($localFieldName, $referencedEntityName, $referencedFieldName)
    {
        $this->_localFieldName = $localFieldName;
        $this->_entityName = $referencedEntityName;
        $this->_fieldName = $referencedFieldName;
    }

    public function getLocalFieldName()
    {
        return $this->_localFieldName;
    }

    public function getEntityName()
    {
        return $this->_entityName;
    }

    public function getFieldName()
    {
        return $this->_fieldName;
    }
}
