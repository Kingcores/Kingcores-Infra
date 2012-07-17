<?php

namespace Bluefin\Lance\Skeleton;

use Bluefin\Lance\SchemaSet;
 
class AbstractBlock
{
    protected $_schemaSet;
    protected $_controllerName;
    protected $_actionName;
    protected $_blockConfig;

    public function __construct(SchemaSet $schemaSet, $controllerName, $actionName, array $config)
    {
        $this->_schemaSet = $schemaSet;
        $this->_controllerName = $controllerName;
        $this->_actionName = $actionName;
        $this->_blockConfig = $config;
    }
}
