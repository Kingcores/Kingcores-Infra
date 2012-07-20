<?php

namespace Bluefin;

/**
 * “无用”类，可替代任何对象，取消该所替代对象的功能。
 */
class Dummy
{
    private static $_instance;

    /**
     * @static
     * @return Dummy
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    public function __call($name, $arguments)
    {
        return self::getInstance();
    }

    /**
     * 只适用于PHP5.3
     * @static
     * @param  $name
     * @param  $arguments
     * @return Dummy
     */
    public static function __callStatic($name, $arguments)
    {
        return self::getInstance();
    }
}
