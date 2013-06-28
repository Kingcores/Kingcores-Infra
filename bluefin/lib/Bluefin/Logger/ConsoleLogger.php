<?php

namespace Bluefin\Logger;

class ConsoleLogger extends VTFormatterLogger
{
    const DEFAULT_MESSAGE_FORMAT = "{{ level }}: [{{ channel }}]{{ message }}\n";

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
        parent::__construct($config, self::DEFAULT_MESSAGE_FORMAT, $context);
    }

    protected function _doLog($formattedMessage)
    {
        echo $formattedMessage;
    }
}
