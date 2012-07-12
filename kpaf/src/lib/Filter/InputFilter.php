<?php

namespace Bluefin\Filter;

use Bluefin\Gateway;
use Bluefin\Convention;
use Bluefin\Exception\InvalidRequestException;
 
class InputFilter extends AbstractFilter implements FilterInterface
{
    public function __construct(Gateway $gateway)
    {
        parent::__construct($gateway);
    }

    public function filter(array $filterOptions = null)
    {
        foreach ($filterOptions as $key => $val)
        {
            $actualValue = _CONTEXT($key);

            if (1 != preg_match('/'.$val.'/', $actualValue))
            {
                throw new InvalidRequestException(
                    _T(
                        'Invalid request parameter: %name%. URL: %url%',
                        Convention::LOCALE_BLUEFIN_DOMAIN,
                        array('%name%' => $key, '%url%' => $this->_gateway->getRequest()->getRequestUri())
                    )
                );
            }
        }

        return true;
    }
}
