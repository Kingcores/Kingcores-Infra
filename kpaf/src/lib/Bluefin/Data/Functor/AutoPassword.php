<?php

namespace Bluefin\Data\Functor;

class AutoPassword implements SupplierInterface
{
    public function supply(array $fieldOption)
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
