<?php

namespace Bluefin\Auth\Persistence;

use Bluefin\App;
use Bluefin\Convention;

class Session implements PersistenceInterface
{
    public static function forgetAll()
    {
        $namespace = _C('app.authSessionNamespace', Convention::DEFAULT_AUTH_SESSION_NAMESPACE);
        \Zend_Session::namespaceUnset($namespace);
    }

    private $_session;
    private $_token;
    private $_lifetime;

    public function __construct($authName, $config)
    {
        $namespace = _C('app.authSessionNamespace', Convention::DEFAULT_AUTH_SESSION_NAMESPACE);
        
        $this->_session = new \Zend_Session_Namespace($namespace);

        $this->_token = $authName;
        $this->_lifetime = array_try_get($config, 'lifetime', \Bluefin\Convention::DEFAULT_AUTH_LIFETIME);

        if (!$this->isEmpty())
        {
            $this->_session->setExpirationSeconds($this->_lifetime, $this->_token);
        }
    }

    public function isEmpty()
    {
        return !$this->_session->__isset($this->_token);
    }

    public function read()
    {
        if ($this->isEmpty())
        {
            return null;
        }

        return $this->_session->__get($this->_token);
    }

    public function write(array $data)
    {
        $this->_session->__set($this->_token, $data);
        $this->_session->setExpirationSeconds($this->_lifetime, $this->_token);
    }

    public function clear()
    {
        $this->_session->__unset($this->_token);
    }
}
