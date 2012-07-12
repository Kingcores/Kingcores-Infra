<?php

namespace Bluefin\Exception;

class ConfigException extends ServerErrorException
{
    public function __construct($message)
    {
        parent::__construct(
            "Invalid configuration! " . $message,
            \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}