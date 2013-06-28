<?php

namespace Bluefin\Exception;

class FileNotFoundException extends ServerErrorException
{
    public function __construct($filename, \Exception $previousException = null)
    {
        parent::__construct(
            "File not found. Path: {$filename}",
            \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR,
            $previousException
        );
    }
}
