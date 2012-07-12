<?php
namespace Bluefin\Filter;

use Bluefin\Gateway;

class AbstractFilter
{
    protected $_gateway;

    public function __construct(Gateway $gateway)
    {
        $this->_gateway = $gateway;
    }
}
