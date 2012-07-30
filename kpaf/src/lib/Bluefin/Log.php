<?php

namespace Bluefin;

use Bluefin\Log\LoggerInterface;

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
     * @var
     */
    protected $_loggers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_channels = array();
        $this->_loggers = array();

        if (!isset(self::$__priorities))
        {
            self::$__priorities = array(
                self::FATAL     => 'FATAL',
                self::ALERT     => 'ALERT',
                self::CRITICAL  => 'CRITICAL',
                self::ERROR     => 'ERROR',
                self::WARNING   => 'WARNING',
                self::INFO      => 'INFO',
                self::VERBOSE   => 'VERBOSE',
                self::DEBUG     => 'DEBUG',
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

    /**
     * Add a logger to a log object
     *
     * @param LoggerInterface $logger
     * @return Log
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->_loggers[] = $logger;

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
    public function log($level, $message, $channel = self::CHANNEL_DEFAULT)
    {
        if (empty($this->_loggers)) return $this;

        if (!is_int($level) || $level < 0 || $level > self::DEBUG)
        {
            throw new \InvalidArgumentException("Invalid log level: {$level}!");
        }

        if (is_array($message))
        {
            $message = var_export($message, true);
        }

        $event = array(
            'timestamp'    => time(),
            'channel'      => (string) $channel,
            'level'        => $level,
            'levelName'    => self::$__priorities[$level],
            'message'      => (string) $message
        );

        foreach ($this->_loggers as $logger)
        {
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
        $this->log(self::FATAL, $message, $channel);
    }

    public function alert($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::ALERT, $message, $channel);
    }

    public function critical($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::CRITICAL, $message, $channel);
    }

    public function error($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::ERROR, $message, $channel);
    }

    public function warning($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::WARNING, $message, $channel);
    }

    public function info($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::INFO, $message, $channel);
    }

    public function verbose($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::VERBOSE, $message, $channel);
    }

    public function debug($message, $channel = self::CHANNEL_DEFAULT)
    {
        $this->log(self::DEBUG, $message, $channel);
    }

    /**
     * Register logging system as an error handler to log PHP errors
     *
     * @link http://www.php.net/manual/en/function.set-error-handler.php
     * @param  Log $logger
     * @return bool
     * @throws \InvalidArgumentException if logger is null
     */
    public static function registerErrorHandler(Log $logger)
    {
        // Only register once per instance
        if (self::$__registeredErrorHandler) {
            return false;
        }

        if (!isset($logger)) {
            throw new \InvalidArgumentException('Invalid Logger specified');
        }

        $errorHandlerMap = array(
            E_NOTICE            => self::INFO,
            E_USER_NOTICE       => self::INFO,
            E_WARNING           => self::WARNING,
            E_CORE_WARNING      => self::WARNING,
            E_USER_WARNING      => self::WARNING,
            E_ERROR             => self::ALERT,
            E_USER_ERROR        => self::ERROR,
            E_CORE_ERROR        => self::FATAL,
            E_RECOVERABLE_ERROR => self::ERROR,
            E_STRICT            => self::DEBUG,
            E_DEPRECATED        => self::DEBUG,
            E_USER_DEPRECATED   => self::DEBUG
        );

        set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) use ($errorHandlerMap, $logger) {
            $errorLevel = error_reporting();

            if ($errorLevel && $errno) {
                if (isset($errorHandlerMap[$errno])) {
                    $level = $errorHandlerMap[$errno];
                } else {
                    $level = Log::VERBOSE;
                }

                /**
                 * @var $logger Log
                 */
                $logger->log($level,
                    $errstr . " (errno: {$errno}, file: {$errfile}, line: {$errline})",
                    self::CHANNEL_ERROR_REPORT);
            }
        });

        self::$__registeredErrorHandler = true;
        return true;
    }

    /**
     * Unregister error handler
     *
     */
    public static function unregisterErrorHandler()
    {
        restore_error_handler();
        self::$__registeredErrorHandler = false;
    }
}
