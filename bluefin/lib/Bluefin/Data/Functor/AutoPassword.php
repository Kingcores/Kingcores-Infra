<?php

namespace Bluefin\Data\Functor;

class AutoPassword implements ProviderInterface
{
    public function apply(array $fieldOption, array $data = null)
    {
        $charTable = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&";
        $maxId = mb_strlen($charTable) - 1;
        $result = '';

        for ($i = 0; $i < 8; $i++)
        {
            $result .= $charTable[rand(0, $maxId)];
        }

        return $result;
    }
}
