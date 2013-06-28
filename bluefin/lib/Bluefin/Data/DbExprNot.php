<?php

namespace Bluefin\Data;

class DbExprNot
{
    protected $_expr;

    public function __construct($expr)
    {
        $this->_expr = $expr;
    }

    public function __toString()
    {
        return $this->_expr;
    }
}
