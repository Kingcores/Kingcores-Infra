<?php

namespace Bluefin;

/**
 * VarText Modifier Wrapper.
 */
class VTMWrapper extends VarTextModifier
{
    private $_handler;
    private $_hasParameter;
    private $_withContext;

    public function __construct($modifierToken, $handler, $hasParameter = false, $withContext = false)
    {
        parent::__construct($modifierToken);

        $this->_handler = $handler;
        $this->_hasParameter = $hasParameter;
        $this->_withContext = $withContext;
    }

    public function process($value, $parameter, $context)
    {
        if ($this->_hasParameter)
        {
            return $this->_withContext ?
                call_user_func($this->_handler, $value, $parameter, $context) :
                call_user_func($this->_handler, $value, $parameter);
        }
        else
        {
            return call_user_func($this->_handler, $value);
        }
    }
}
