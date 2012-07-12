<?php

namespace Bluefin\Data\Functor;

class UnpackUuid implements FormatterInterface
{
    public function format($rawValue, array $fieldOption)
    {
        $hex = bin2hex($rawValue);

        return    substr($hex, 0, 8) .
            '-' . substr($hex, 8, 4) .
            '-' . substr($hex, 12, 4) .
            '-' . substr($hex, 16, 4) .
            '-' . substr($hex, 20, 12);
    }
}
