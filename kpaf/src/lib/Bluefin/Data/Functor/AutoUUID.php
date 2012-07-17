<?php

namespace Bluefin\Data\Functor;

use Bluefin\Data\Type;

class AutoUUID implements SupplierInterface
{
    public function supply(array $fieldOption)
    {
        $uuid = uuid_gen();

        return $uuid;
    }
}