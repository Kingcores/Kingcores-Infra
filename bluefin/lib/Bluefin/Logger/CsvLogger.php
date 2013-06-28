<?php

namespace Bluefin\Logger;

use Bluefin\Log;
use Bluefin\Common;
use Bluefin\VarText;

class CsvLogger extends FileLogger
{
    protected $_fields;

    public function __construct(array $config, array $context = null)
    {
        parent::__construct($config, $context);

        $this->_fields = array_try_get($config, 'fields');

        if (empty($this->_fields))
        {
            throw new \Bluefin\Exception\ConfigException("Missing fields config for csv logger.");
        }
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
        $fields = $this->_fields;

        foreach ($fields as &$value)
        {
            $value = VarText::parseVarText($value, $event);
        }

        $result = @fputcsv($this->_stream, $fields);

        if (false === $result)
        {
            error_log("Unable to write csv data into log stream!");
        }
    }

    /**
     * Close the stream resource.
     *
     * @return void
     */
    public function shutdown()
    {
        if (is_resource($this->_stream))
        {
            @fclose($this->_stream);
        }
    }
}
