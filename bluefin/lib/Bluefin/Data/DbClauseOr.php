<?php

namespace Bluefin\Data;

class DbClauseOr
{
    protected $_exprs;

    public function __construct(array $exprs)
    {
        $this->_exprs = $exprs;
    }

    public function getExpressions()
    {
        return $this->_exprs;
    }
}
