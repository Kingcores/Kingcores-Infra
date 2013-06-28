<?php

namespace Bluefin\Persistence;
 
interface PersistenceInterface
{
    function getHandlerObject();
    function get($key = null, $default = null);

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    function set($key, $value, $expiration = null);

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    function append($key, $value);

    /**
     * @param $key
     * @param $value
     * @return boolean
     */
    function appendUnique($key, $value);

    /**
     * @param $key
     * @return boolean
     */
    function remove($key);

    function reset(array $data);

    /**
     * @param array $data
     * @return boolean
     */
    function merge(array $data);
    function has($key);
    function isEmpty();

    /**
     * @return boolean
     */
    function clear();
    function increase($key, $value = null);
    function decrease($key, $value = null);
}
