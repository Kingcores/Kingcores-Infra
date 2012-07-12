<?php

namespace Bluefin\Exception;

class InvalidRequestException extends RequestException
{
    public function __construct($message = null)
    {
        parent::__construct($message, \Bluefin\Common::HTTP_BAD_REQUEST);
    }
}
