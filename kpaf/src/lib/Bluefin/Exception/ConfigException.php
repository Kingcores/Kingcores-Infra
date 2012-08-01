<?php

namespace Bluefin\Exception;

class ConfigException extends ServerErrorException
{
    public function __construct($message, $previousException = null)
    {
        parent::__construct(
            "Invalid configuration! " . $message,
            $previousException,
            \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}