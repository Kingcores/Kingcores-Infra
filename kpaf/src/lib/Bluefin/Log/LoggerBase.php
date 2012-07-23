<?php

namespace Bluefin\Log;

class LoggerBase
{
    protected $_format;
    protected $_level;
    protected $_channels = array(Log::CHANNEL_GENERAL => true, Log::CHANNEL_ERROR_REPORT => true);

    public function __construct(array $config)
    {
        $this->_format = array_try_get($config, 'format', '[{%timestamp|date=c}][{%levelName}][{%channel}]{%message}');
        $this->_level = (int) array_try_get($config, 'level', Log::INFO);

        if ($this->_level < Log::FATAL || $this->_level > Log::DEBUG)
        {
            throw new \Bluefin\Exception\ConfigException("Invalid log level: {$this->_level}!");
        }

        $channels = array_try_get($config, 'channels');

        if (!empty($channels))
        {
            foreach ($channels as $name => $status)
            {
                \Bluefin\Data\Type::convertBool('channelStatus', $status);

                $this->turnChannel($name, $status);
            }
        }
    }

    public function setFormat($format)
    {
        $this->_format = $format;
    }

    public function getFormat()
    {
        return $this->_format;
    }

    public function setLevel($level)
    {
        $this->_level = $level;
    }

    public function getLevel()
    {
        return $this->_level;
    }

    public function turnChannel($channel, $on = true)
    {
        $this->_channels[$channel] = (bool) $on;
    }

    public function getChannels()
    {
        return $this->_channels;
    }
}
