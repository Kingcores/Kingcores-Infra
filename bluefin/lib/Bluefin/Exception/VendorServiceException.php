<?php

namespace Bluefin\Exception;

class VendorServiceException extends RequestException
{
    public function __construct($message = null, \Exception $previousException = null)
    {
        parent::__construct($message, \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE, $previousException);
    }
}
