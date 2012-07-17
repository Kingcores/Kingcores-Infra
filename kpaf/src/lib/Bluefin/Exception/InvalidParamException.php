<?php

namespace Bluefin\Exception;

use Bluefin\Convention;

class InvalidParamException extends RequestException
{
    public function __construct($paramName, $paramType = null)
    {
        isset($paramType) || ($paramType = 'parameter');

        $message = _T(
            'Invalid "%name%" value as [%type%].',
            Convention::LOCALE_BLUEFIN_DOMAIN,
            array('%name%' => _META_($paramName), '%type%' => _BUILTIN_($paramType))
        );

        parent::__construct($message, \Bluefin\Common::HTTP_BAD_REQUEST);
    }
}
