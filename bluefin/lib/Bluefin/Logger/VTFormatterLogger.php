<?php

namespace Bluefin\Logger;

use Bluefin\Common;
use Bluefin\VarText;

class ConsoleLogger extends LoggerBase implements LoggerInterface
{
    const DEFAULT_MESSAGE_FORMAT = "{{ level }}: [{{ channel }}]{{ message }}\n";

    protected $_messageFormat;

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
     * @param array $context
     * @throws \Bluefin\Exception\ConfigException
     * @throws \RuntimeException
     */
    public function __construct(array $config, array $context = null)
    {
        parent::__construct($config, $context);

        $this->_messageFormat = array_try_get($config, 'format', self::DEFAULT_MESSAGE_FORMAT);
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
        if ($event['message'])

        echo VarText::parseVarText($this->_messageFormat, $event);
    }

    /**
     * Close the stream resource.
     *
     * @return void
     */
    public function shutdown()
    {
    }
}
