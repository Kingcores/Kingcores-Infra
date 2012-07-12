<?php

namespace Bluefin\Lance\Exception;

use Bluefin\Exception\BluefinException;
 
class GrammarException extends BluefinException
{
    public function __construct($message)
    {
        parent::__construct($message, 0);
    }
}
