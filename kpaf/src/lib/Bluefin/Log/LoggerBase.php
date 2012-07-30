<?php

namespace Bluefin\Log;

use Bluefin\Convention;
use Bluefin\Log;
use Bluefin\VarText;
use Bluefin\VarModifierHandler;

class LoggerBase
{
    protected $_format;
    protected $_level;
    protected $_channels = array(Log::CHANNEL_DEFAULT => true);

    /**
     * @var \Bluefin\VarText
     */
    protected $_formatTextProcessor;

    public function __construct(array $config, array $context = null)
    {
        $this->_format = array_try_get($config, 'format', Convention::DEFAULT_LOG_FORMAT);
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

        $handlers = array(
            VarModifierHandler::getPredefinedHandler('date')
        );

        $this->_formatTextProcessor = new VarText($context, false, $handlers);
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

    protected function _formatMessage(array $message)
    {
        $this->_formatTextProcessor->setContext($message);
        return $this->_formatTextProcessor->parse($this->_format);
    }
}
