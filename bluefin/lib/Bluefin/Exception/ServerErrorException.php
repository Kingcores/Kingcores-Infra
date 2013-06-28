<?php

namespace Bluefin\Exception;
 
class ServerErrorException extends BluefinException
{
    public function __construct($message = null, $code = \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR, \Exception $previousException = null)
    {
        parent::__construct($message, $code, $previousException);
    }
}
