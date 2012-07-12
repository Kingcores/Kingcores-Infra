<?php

namespace Bluefin\Data\Functor;

class Context implements SupplierInterface
{
    private $_contextPath;
    private $_default;

    public function __construct($contextPath, $default = null)
    {
        $this->_contextPath = $contextPath;
        $this->_default = $default;
    }

    public function supply(array $fieldOption)
    {
        return _CONTEXT($this->_contextPath, $this->_default, false);
    }
}
