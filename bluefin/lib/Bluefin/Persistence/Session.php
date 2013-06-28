<?php

namespace Bluefin\Persistence;

use Bluefin\Convention;
use Bluefin\App;

class Session implements PersistenceInterface
{
    protected $_namespace;

    public function __construct(array $options)
    {
        $this->_namespace = array_try_get($options, 'namespace');
    }

    public function getHandlerObject()
    {
        App::assert(false, 'Not supported!');
    }

    public function get($key = null, $default = null)
    {
        $data = isset($this->_namespace) ? array_try_get($_SESSION, $this->_namespace) : $_SESSION;
        if (!isset($data)) return null;
        return isset($key) ? array_try_get($data, $key, $default) : $data;
    }

    public function set($key, $value, $expiration = null)
    {
        if (isset($this->_namespace))
        {
            if (isset($_SESSION[$this->_namespace]))
            {
                $_SESSION[$this->_namespace][$key] = $value;
            }
            else
            {
                $_SESSION[$this->_namespace] = [ $key => $value ];
            }
        }
        else
        {
            $_SESSION[$key] = $value;
        }

        return true;
    }

    public function remove($key)
    {
        if (isset($this->_namespace))
        {
            if (isset($_SESSION[$this->_namespace]))
            {
                unset($_SESSION[$this->_namespace][$key]);
            }
        }
        else
        {
            unset($_SESSION[$key]);
        }

        return true;
    }

    public function reset(array $data)
    {
        if (isset($this->_namespace))
        {
            $_SESSION[$this->_namespace] = $data;
            return true;
        }
        else
        {
            session_unset();
            return $this->merge($data);
        }
    }

    public function has($key)
    {
        if (isset($this->_namespace))
        {
            if (isset($_SESSION[$this->_namespace]))
            {
                return isset($_SESSION[$this->_namespace][$key]);
            }

            return false;
        }

        return isset($_SESSION[$key]);
    }

    public function append($key, $value)
    {
        $list = $this->get($key);

        if (isset($list))
        {
            App::assert(is_array($list));

            $list[] = $value;
        }
        else
        {
            $list = [$value];
        }

        return $this->set($key, $list);
    }

    public function appendUnique($key, $value)
    {
        $list = $this->get($key);

        if (isset($list))
        {
            App::assert(is_array($list));

            array_push_unique($list, $value);
        }
        else
        {
            $list = [$value];
        }

        return $this->set($key, $list);
    }

    public function merge(array $data)
    {
        $allDone = true;

        foreach ($data as $key => $value)
        {
            $allDone &= $this->set($key, $value);
        }

        return $allDone;
    }

    public function isEmpty()
    {
        return isset($this->_namespace) ? empty($_SESSION[$this->_namespace]) : empty($_SESSION);
    }

    public function clear()
    {
        if (isset($this->_namespace))
        {
            unset($_SESSION[$this->_namespace]);
        }
        else
        {
            session_unset();
        }

        return true;
    }

    public function increase($key, $value = null)
    {
        isset($value) || ($value = 1);
        $prev = $this->get($key, 0);
        $prev += $value;
        $this->set($key, $prev);

        return $prev;
    }

    public function decrease($key, $value = null)
    {
        isset($value) || ($value = 1);
        $prev = $this->get($key, 0);
        $prev -= $value;
        $this->set($key, $prev);

        return $prev;
    }
}
