<?php

namespace Bluefin\Logger;

use Bluefin\Convention;
use Bluefin\Log;
use Bluefin\VarText;

class LoggerBase
{
    protected $_level;
    protected $_channels = [Log::CHANNEL_DEFAULT => true];

    public function __construct(array $config, array $context = null)
    {
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
                if (is_int($name))
                {
                    $name = $status;
                    $status = true;
                }
                else
                {
                    \Bluefin\Data\Type::convertBool('channelStatus', $status);
                }

                $this->turnChannel($name, $status);
            }
        }
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
