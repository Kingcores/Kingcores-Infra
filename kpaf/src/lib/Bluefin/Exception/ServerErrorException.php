<?php

namespace Bluefin\Exception;
 
class ServerErrorException extends BluefinException
{
    public function __construct($message, $code = \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR)
    {
        parent::__construct($message, $code);
    }
}