<?php

namespace Bluefin\Data\Functor;

use Bluefin\Data\Model;

class VarTextProvider implements ProviderInterface
{
    private $_varText;

    public function __construct($varText)
    {
        $this->_varText = $varText;
    }

    public function apply(array $fieldOption, array $data = null)
    {
        return \Bluefin\VarText::parseVarText($this->_varText, $data);
    }
}