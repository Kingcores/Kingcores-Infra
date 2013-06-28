<?php

namespace Common\Exception;

use Bluefin\Exception\RequestException;
use Common\Data\Event;

class EventException extends RequestException
{
    public function __construct($source, $code, $level = Event::LEVEL_ERROR, array $params = null, \Exception $previousException = null)
    {
        $ec = Event::make($level, $source, $code);

        parent::__construct(Event::getMessage($this->getCode(), $params), $ec, $previousException);
    }
}
