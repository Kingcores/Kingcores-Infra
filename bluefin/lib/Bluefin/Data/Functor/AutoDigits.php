<?php

namespace Bluefin\Data\Functor;

use Bluefin\Data\Type;

class AutoDigits implements ProviderInterface
{
    public function apply(array $fieldOption, array $data = null)
    {
        $digits = (int)array_try_get($fieldOption, Type::ATTR_LENGTH, 1);
        $result = '';

        for ($i = 0; $i < $digits; $i++)
        {
            $result .= rand(0, 9);
        }

        return $result;
    }
}
