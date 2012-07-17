<?php

namespace Bluefin\Exception;
 
class DatabaseException extends ServerErrorException
{
    public function __construct($message)
    {
        parent::__construct($message, \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR);
    }
}
