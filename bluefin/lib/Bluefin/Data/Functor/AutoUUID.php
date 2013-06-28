<?php

namespace Bluefin\Data\Functor;

use Bluefin\Data\Type;

class AutoUUID implements ProviderInterface
{
    public function apply(array $fieldOption, array $data = null)
    {
        $uuid = uuid_gen();

        return $uuid;
    }
}