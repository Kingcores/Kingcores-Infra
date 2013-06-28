<?php

namespace Bluefin;

/**
 * VarText Modifier.
 */
class VarTextModifier
{
    private $_modifierToken;

    public function __construct($modifierToken)
    {
        $this->_modifierToken = $modifierToken;
    }

    public function process($value, $parameter, $context)
    {
        return $value;
    }

    public function getModifierToken()
    {
        return $this->_modifierToken;
    }
}
