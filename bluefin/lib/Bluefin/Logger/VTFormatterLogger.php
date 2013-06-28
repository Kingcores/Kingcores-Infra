<?php

namespace Bluefin\Logger;

use Bluefin\Log;
use Bluefin\VarText;

class VTFormatterLogger extends LoggerBase implements LoggerInterface
{
    protected $_regularFormat;
    protected $_currentFormat;
    protected $_usingSpecialFormat;

    /**
     * Constructor
     *
     * config:
     *   path
     *   filename
     *   format
     *   mode
     *
     * @param array $config
     * @param array $defaultFormat
     * @param array $context
     */
    public function __construct(array $config, $defaultFormat, array $context = null)
    {
        parent::__construct($config, $context);

        $this->_regularFormat = array_try_get($config, 'format', $defaultFormat);
        $this->_resetFormat();
    }

    /**
     * Write a message to the log.
     *
     * @param array $event event data
     * @return void
     * @throws \RuntimeException
     */
    public function log(array $event)
    {
        if (isset($event[Log::EVENT_MESSAGE]))
        {
            $this->_doLog(VarText::parseVarText($this->_currentFormat, $event));
        }

        if ($this->_usingSpecialFormat)
        {
            $this->_resetFormat();
        }
    }

    public function setLogWithoutCR()
    {
        if (substr($this->_currentFormat, -1, 1) == "\n")
        {
            $this->_usingSpecialFormat = true;
            $this->_currentFormat = substr($this->_currentFormat, 0, -1);
        }
    }

    public function setLogMessageOnly()
    {
        $this->_usingSpecialFormat = true;
        $this->_currentFormat = "{{ message }}\n";
    }

    /**
     * Close the stream resource.
     *
     * @return void
     */
    public function shutdown()
    {
    }

    protected function _doLog($formattedMessage)
    {
    }

    protected function _resetFormat()
    {
        $this->_currentFormat = $this->_regularFormat;
        $this->_usingSpecialFormat = false;
    }
}
