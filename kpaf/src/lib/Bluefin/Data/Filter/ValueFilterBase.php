<?php

namespace Bluefin\Data\Filter;
 
class ValueFilterBase
{
    private $_failed;
    private $_message;

    public function isFailed()
    {
        return $this->_failed;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    protected function _reset()
    {
        $this->_failed = false;
        $this->_message = null;
    }

    protected function _error($message)
    {
        $this->_failed = true;
        $this->_message = $message;
    }
}
