<?php

namespace Bluefin\Exception;

class BluefinException extends \Exception
{
    public function __construct($message, $previousException = null, $code = \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR)
    {
        parent::__construct($message ? $message : \Bluefin\Common::getStatusCodeMessage($code), $code, $previousException);
    }
}
