<?php

namespace Bluefin\Exception;

class UnauthorizedException extends RequestException
{
    public function __construct()
    {
        parent::__construct(
            null,
            \Bluefin\Common::HTTP_UNAUTHORIZED
        );
    }
}
