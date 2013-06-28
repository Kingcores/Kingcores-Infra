<?php

namespace Bluefin\Data;

class DbCondition
{
    protected $_expr;

    public function __construct($expr)
    {
        $this->_expr = (string)$expr;
    }

    public function __toString()
    {
        return $this->_expr;
    }
}
