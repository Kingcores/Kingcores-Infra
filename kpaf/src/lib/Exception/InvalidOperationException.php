<?php

namespace Bluefin\Exception;
 
class InvalidOperationException extends ServerErrorException
{
    public function __construct($message = null)
    {
        parent::__construct($message);
    }
}
