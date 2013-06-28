<?php

namespace Bluefin\Logger;

use Bluefin\Log;
use Bluefin\Common;
use Bluefin\VarText;

class FileLogger extends VTFormatterLogger
{
    const DEFAULT_MESSAGE_FORMAT = "[{{timestamp|date='Y-m-d H:i:s T'}}][{{level}}][{{channel}}]{{message}}\n";

    /**
     * @var array|null|resource|string
     */
    protected $_stream = null;

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

        if (!array_key_exists('path', $config))
        {
            throw new \Bluefin\Exception\ConfigException("'path' is missing from FileLogger config!");
        }

        $path = $config['path'];

        ensure_dir_exist($path, Common::DIR_MODE_OWNER_WRITE_OTHER_READONLY);

        if (!array_key_exists('filename', $config))
        {
            throw new \Bluefin\Exception\ConfigException("'filename' is missing from FileLogger config!");
        }

        $filename = $config['filename'];

        $path = VarText::parseVarText($path);

        $filename = VarText::parseVarText($filename);

        $mode = array_try_get($config, 'mode', 'a');

        $fullPath = build_path($path, $filename);

        if (! $this->_stream = @fopen($fullPath, $mode, false))
        {
            throw new \RuntimeException("{$fullPath}' cannot be opened with mode '{$mode}!");
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
        if (is_array($event[Log::EVENT_MESSAGE]))
        {
            $event[Log::EVENT_MESSAGE] = \Symfony\Component\Yaml\Yaml::dump($event[Log::EVENT_MESSAGE], 0);
        }

        parent::log($event);
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

    protected function _doLog($formattedMessage)
    {
        $result = @fwrite($this->_stream, $formattedMessage);

       if (false === $result)
       {
           error_log("Unable to write to log stream!");
       }
    }
}
