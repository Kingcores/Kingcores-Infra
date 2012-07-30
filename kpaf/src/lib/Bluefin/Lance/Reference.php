<?php

namespace Bluefin\Lance;

class Reference
{
    private $_localFieldName;
    private $_entityFullName;
    private $_fieldName;

    public function __construct($localFieldName, $referencedEntityName, $referencedFieldName)
    {
        $this->_localFieldName = $localFieldName;
        $this->_entityFullName = $referencedEntityName;
        $this->_fieldName = $referencedFieldName;
    }

    public function getLocalFieldName()
    {
        return $this->_localFieldName;
    }

    public function getEntityFullName()
    {
        return $this->_entityFullName;
    }

    public function getFieldName()
    {
        return $this->_fieldName;
    }
}
