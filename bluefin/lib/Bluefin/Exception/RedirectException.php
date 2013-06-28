<?php

namespace Bluefin\Exception;

use Exception;

class RedirectException extends Exception
{
    public $targetUrl;
    public $statusCode;

    public function __construct($url, $code)
    {
        $this->targetUrl = $url;
        $this->statusCode = $code;
    }
}
