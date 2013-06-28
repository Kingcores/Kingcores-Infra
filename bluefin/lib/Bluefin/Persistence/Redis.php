<?php

namespace Bluefin\Persistence;

use Bluefin\App;
use Bluefin\Log;

class Redis implements PersistenceInterface
{
    protected $_redis;
    protected $_lifetime;
    protected $_prefix;

    public function __construct(array $options)
    {
        $this->_redis = new \Redis();

        if (!array_key_exists('host', $options))
        {
            throw new \Bluefin\Exception\ConfigException("Missing 'host' config for redis!");
        }

        $host = array_try_get($options, 'host');
        if (!isset($host))
        {
            throw new \Bluefin\Exception\ConfigException("Missing 'host' config for redis!");
        }

        $port = array_try_get($options, 'port', 6379);
        $password = array_try_get($options, 'password');
        $database = array_try_get($options, 'database', 0);
        $this->_lifetime = array_try_get($options, 'lifetime');
        $this->_prefix = array_try_get($options, 'prefix', '');

        $this->_redis->connect($host, $port);

        if (isset($password))
        {
            $this->_redis->auth($password);
        }

        $this->_redis->select($database);

        App::GetInstance()->log()->debug("Redis cache [{$host}:{$port}#{$database}] is enabled.", Log::CHANNEL_DIAG);
    }

    public function getHandlerObject()
    {
        return $this->_redis;
    }

    public function get($key = null, $default = null)
    {
        if (isset($key))
        {
            isset($value) || ($value = $default);
            $value = $this->_redis->get($this->_prefix . $key);
            ($value !== false) || ($value = null);
        }
        else
        {
            $keys = $this->_redis->keys('*');
            $value = $this->_redis->mGet($keys);
        }

        return $value;
    }

    public function set($key, $value, $expiration = null)
    {
        isset($expiration) || ($expiration = $this->_lifetime);

        if (isset($expiration))
        {
            return true === $this->_redis->setex($this->_prefix . $key, $expiration, $value);
        }
        else
        {
            return true === $this->_redis->set($this->_prefix . $key, $value);
        }
    }

    public function append($key, $value)
    {
        if (isset($this->_lifetime))
        {
            $result = $this->_redis->lPush($this->_prefix . $key, $value);
            $this->_redis->setTimeout($this->_prefix . $key, $this->_lifetime);
            return $result;
        }
        else
        {
            return false !== $this->_redis->lPush($this->_prefix . $key, $value);
        }
    }

    public function appendUnique($key, $value)
    {
        if (isset($this->_lifetime))
        {
            $result = $this->_redis->multi()
                ->sAdd($this->_prefix . $key, $value)
                ->setTimeout($this->_prefix . $key, $this->_lifetime)
                ->exec();
            return false !== $result[0];
        }
        else
        {
            return false !== $this->_redis->sAdd($this->_prefix . $key, $value);
        }
    }

    public function remove($key)
    {
        return $this->_redis->delete($this->_prefix . $key) > 0;
    }

    public function reset(array $data)
    {
        $this->clear();
        $this->merge($data);
    }

    public function merge(array $data)
    {
        if (strlen($this->_prefix) > 0)
        {
            $trans = [];
            foreach ($data as $key => $value)
            {
                $trans[$this->_prefix . $key] = $value;
            }
            $data = $trans;
        }

        if (isset($this->_lifetime))
        {
            $result = $multiMode = $this->_redis->multi()
                ->mset($data);
            foreach ($data as $key => $value)
            {
                $multiMode->setTimeout($key, $this->_lifetime);
            }
            $multiMode->exec();

            return $result[0];
        }
        else
        {
            return $this->_redis->mset($data);
        }
    }

    public function has($key)
    {
        return $this->_redis->exists($this->_prefix . $key);
    }

    public function isEmpty()
    {
        return $this->_redis->dbSize() == 0;
    }

    public function clear()
    {
        return $this->_redis->flushDB();
    }

    public function increase($key, $value = null)
    {
        if (isset($value))
        {
            return $this->_redis->incrBy($this->_prefix . $key, $value);
        }
        else
        {
            return $this->_redis->incr($this->_prefix . $key);
        }
    }

    public function decrease($key, $value = null)
    {
        if (isset($value))
        {
            return $this->_redis->decrBy($this->_prefix . $key, $value);
        }
        else
        {
            return $this->_redis->decr($this->_prefix . $key);
        }
    }
}
