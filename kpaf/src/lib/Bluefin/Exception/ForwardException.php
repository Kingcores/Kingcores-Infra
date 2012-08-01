<?php

namespace Bluefin\Exception;

use Exception;

class ForwardException extends Exception
{
    public $namespace;
    public $moduleName;
    public $controllerName;
    public $actionName;

    public function __construct($namespace, $moduleName, $controllerName, $actionName)
    {
        $this->namespace = $namespace;
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
        $this->actionName = $actionName;
    }
}
