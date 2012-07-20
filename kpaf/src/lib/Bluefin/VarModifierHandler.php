<?php

namespace Bluefin;

class VarModifierHandler
{
    public static function getPredefinedHandler($token)
    {
        switch ($token)
        {
            case 'date':
                return new VarModifierHandler('date', function($value, $parameter) { return date($parameter, $value); }, true);
        }

        return null;
    }

    private $_modifierToken;
    private $_hasParameter;
    private $_handler;

    public function __construct($modifierToken, $handler, $withParameter = false)
    {
        $this->_modifierToken = $modifierToken;
        $this->_hasParameter = $withParameter;
        $this->_handler = $handler;
    }

    public function getHandler()
    {
        return $this->_handler;
    }

    public function getModifierToken()
    {
        return $this->_modifierToken;
    }

    public function hasParameter()
    {
        return $this->_hasParameter;
    }
}
