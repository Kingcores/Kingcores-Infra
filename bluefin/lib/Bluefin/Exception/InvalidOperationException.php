<?php

namespace Bluefin\Exception;
 
class InvalidOperationException extends ServerErrorException
{
    public function __construct($message, \Exception $previousException = null)
    {
        parent::__construct($message, \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR, $previousException);
    }
}
