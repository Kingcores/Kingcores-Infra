<?php

namespace Bluefin;

use Bluefin\Logger\LoggerInterface;

/**
 * Log
 */
class Log
{
    const FATAL     = 0;
    const ALERT     = 1;
    const CRITICAL  = 2;
    const ERROR     = 3;
    const WARNING   = 4;
    const INFO      = 5;
    const VERBOSE   = 6;
    const DEBUG     = 7;

    const CHANNEL_DEFAULT = 'default';
    const CHANNEL_DIAG = 'diag';

    const EVENT_TIMESTAMP = 'timestamp';
    const EVENT_LEVEL = 'level';
    const EVENT_CHANNEL = 'channel';
    const EVENT_MESSAGE = 'message';

    /**
     * Registered error handler
     *
     * @var boolean
     */
    protected static $__registeredErrorHandler = false;

    /**
     * List of priority code => priority (short) name
     *
     * @var array
     */
    protected static $__priorities;

    /**
     * Channels
     *
     * @var array
     */
    protected $_channels;

    /**
     * Listening loggers
     *
     * @var array
     */
    protected $_loggers;

    /**
     * Maximum Level
     *
     * @var int
     */
    protected $_maxLevel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_channels = [];
        $this->_loggers = [];
        $this->_maxLevel = 0;

        if (!isset(self::$__priorities))
        {
            self::$__priorities = array(
                self::FATAL     => 'fatal',
                self::ALERT     => 'alert',
                self::CRITICAL  => 'critical',
                self::ERROR     => 'error',
                self::WARNING   => 'warning',
                self::INFO      => 'info',
                self::VERBOSE   => 'verbose',
                self::DEBUG     => 'debug',
            );
        }
    }

    /**
     * Shutdown all logger
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->_loggers as $logger)
        {
            /**
             * @var $logger LoggerInterface
             */

            try
            {
                $logger->shutdown();
            }
            catch (\Exception $e)
            {
            }
        }
    }

    public function isLogOn($level, $channel = null)
    {
        if ($level > $this->_maxLevel) return false;

        if (isset($channel) && !array_try_get($this->_channels, $channel, false))
        {
            return false;
        }

        return true;
    }

    /**
     * Add a logger to a log object
     *
     * @param LoggerInterface $logger
     * @return Log
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->_loggers[] = $logger;

        if ($logger->getLevel() > $this->_maxLevel)
        {
            $this->_maxLevel = $logger->getLevel();
        }

        foreach ($logger->getChannels() as $channel => $on)
        {
            if ($on)
            {
                $this->_channels[$channel] = true;
            }
        }

        return $this;
    }

    /**
     * Send a log message
     *
     * @param int $level
     * @param $message
     * @param string $channel
     * @return Log
     * @throws \InvalidArgumentException
     */
    public function log($level, $channel, $message)
    {
        if (empty($this->_loggers)) return $this;

        if (!is_int($level) || $level < 0 || $level > self::DEBUG)
        {
            throw new \InvalidArgumentException("Invalid log level: {$level}!");
        }

        $event = array(
            self::EVENT_TIMESTAMP    => time(),
            self::EVENT_CHANNEL      => (string) $channel,
            self::EVENT_LEVEL        => self::$__priorities[$level],
            self::EVENT_MESSAGE      => $message
        );

        foreach ($this->_loggers as $logger)
        {
            /**
             * @var $logger LoggerInterface
             */
            if ($logger->getLevel() < $level) continue;

            $channels = $logger->getChannels();

            if (array_key_exists($channel, $channels) && $channels[$channel])
            {
                /**
                 * @var $logger LoggerInterface
                 */
                $logger->log($event);
            }
        }

        return $this;
    }

    public function fatal($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::FATAL, $channel, $message);
    }

    public function alert($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::ALERT, $channel, $message);
    }

    public function critical($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::CRITICAL, $channel, $message);
    }

    public function error($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::ERROR, $channel, $message);
    }

    public function warning($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::WARNING, $channel, $message);
    }

    public function info($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::INFO, $channel, $message);
    }

    public function verbose($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::VERBOSE, $channel, $message);
    }

    public function debug($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::DEBUG, $channel, $message);
    }
}
