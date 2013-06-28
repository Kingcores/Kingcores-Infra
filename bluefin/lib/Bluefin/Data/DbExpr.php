<?php

namespace Bluefin\Data;

class DbExpr
{
    protected $_expr;
    protected $_isVarText;

    public function __construct($expr, $isVarText = false)
    {
        $this->_expr = (string)$expr;
        $this->_isVarText = $isVarText;
    }

    public function isVarText()
    {
        return $this->_isVarText;
    }

    public function __toString()
    {
        return $this->_expr;
    }
}
