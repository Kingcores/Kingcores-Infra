<?php

namespace Bluefin\Log;

use Bluefin\Common;

class FileLogger extends LoggerBase implements LoggerInterface
{
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
        parent::__construct($config, $context);

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

        $path = $this->_formatTextProcessor->parse($path);

        $filename = $this->_formatTextProcessor->parse($filename);

        $mode = array_try_get($config, 'mode', 'a');

        $fullPath = build_path($path, $filename);

        if (! $this->_stream = @fopen($fullPath, $mode, false))
        {
            throw new \RuntimeException(sprintf(
                '"%s" cannot be opened with mode "%s"',
                $fullPath,
                $mode
            ));
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
        $result = @fwrite($this->_stream, $this->_formatMessage($event));

        if (false === $result)
        {
            error_log("Unable to write to log stream!");
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
