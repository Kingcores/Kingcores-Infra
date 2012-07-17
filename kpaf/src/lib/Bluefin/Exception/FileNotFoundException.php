<?php

namespace Bluefin\Exception;

class FileNotFoundException extends ServerErrorException
{
    public function __construct($filename)
    {
        parent::__construct(
            _T(
                'File not found. Path: %name%',
                'bluefin',
                array('%name%' => $filename)
            ),
            \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
