<?php

namespace Bluefin\Data\Functor;

use Bluefin\Data\Type;

class AutoDigits implements SupplierInterface
{
    public function supply(array $fieldOption)
    {
        $digits = (int)array_try_get($fieldOption, Type::FILTER_INT_DIGITS, 1);
        $result = '';

        for ($i = 0; $i < $digits; $i++)
        {
            $result .= rand(0, 9);
        }

        return $result;
    }
}
