<?php

namespace Bluefin\Exception;
 
class InvalidOperationException extends ServerErrorException
{
    public function __construct($message, $previousException = null)
    {
        parent::__construct($message, $previousException);
    }
}
