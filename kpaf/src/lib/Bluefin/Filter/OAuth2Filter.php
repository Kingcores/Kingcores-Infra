<?php

namespace Bluefin\Filter;

use Bluefin\Gateway;
use Bluefin\Convention;
use Bluefin\Auth\OAuth2Client;

class OAuth2Filter extends AbstractFilter implements FilterInterface
{
    const KEYWORD_AUTH_RESOURCE = 'res';
    const KEYWORD_AUTH_OPERATION = 'op';

    public function __construct(Gateway $gateway)
    {
        parent::__construct($gateway);
    }

    public function filter(array $filterOptions = null)
    {
        $resource = array_try_get($filterOptions, self::KEYWORD_AUTH_RESOURCE);
        $operation = array_try_get($filterOptions, self::KEYWORD_AUTH_OPERATION);

        $client = new OAuth2Client($this->_gateway->getRequest());




        return true;
    }
}
