<?php

namespace Bluefin\Data\Functor;

interface FormatterInterface
{
    function format($rawValue, array $fieldOption);
}
