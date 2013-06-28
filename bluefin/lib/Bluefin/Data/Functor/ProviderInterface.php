<?php

namespace Bluefin\Data\Functor;

use Bluefin\Data\Model;

interface ProviderInterface
{
    function apply(array $fieldOption, array $data = null);
}
