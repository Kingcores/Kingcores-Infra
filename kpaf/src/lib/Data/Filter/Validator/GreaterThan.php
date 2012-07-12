<?php

namespace Bluefin\Data\Validator;

use Bluefin\Convention;
use Bluefin\Data\Filter\ValueFilterInterface;
use Bluefin\Data\Filter\ValueFilterBase;

class GreaterThan extends ValueFilterBase implements ValueFilterInterface
{
    public function apply($name, $value, $context)
    {
        $context = (int)$context;
        
        if ($value <= $context)
        {
            $this->_error(
                _T(
                    'Value of "%name%" ["%value%"] is not greater than "%max%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array(
                        '%name%' => _META_($name),
                        '%value%' => $value,
                        '%max%' => $context
                    )
                )
            );
        }
    }
}
