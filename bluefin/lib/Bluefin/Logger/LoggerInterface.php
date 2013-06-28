<?php

namespace Bluefin\Logger;

interface LoggerInterface
{
    /**
     * Get logger level
     *
     * @abstract
     * @return int
     */
    public function getLevel();

    /**
     * Set logger level
     *
     * @abstract
     * @param $level
     * @return mixed
     */
    public function setLevel($level);

    /**
     * @abstract
     * @param $channel
     * @param bool $on
     * @return mixed
     */
    public function turnChannel($channel, $on = true);

    /**
     * @abstract
     * @return mixed
     */
    public function getChannels();

    /**
     * Write a log event
     *
     * @param  array $event
     * @return LoggerInterface
     */
    public function log(array $event);

    /**
     * Perform shutdown activities
     *
     * @return void
     */
    public function shutdown();
}
