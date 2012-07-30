<?php

namespace Bluefin\Log;

use Bluefin\Common;
use Bluefin\VarText;

class ConsoleLogger extends LoggerBase implements LoggerInterface
{
    /**
     * @var \Bluefin\VarText
     */
    protected $_formatTextProcessor;

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
        if (!array_key_exists('format', $config))
        {
            $config['format'] = "{%levelName}: [{%channel}]{%message}\n";
        }

        parent::__construct($config, $context);
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
        echo $this->_formatMessage($event);
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
