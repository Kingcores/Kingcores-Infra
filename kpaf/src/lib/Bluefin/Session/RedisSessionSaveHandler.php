<?php

namespace Bluefin\Session;

use Bluefin\App;

/**
 * Redis save handler for Zend_Session
 */
class RedisSessionSaveHandler implements SaveHandlerInterface
{
    /**
     * Redis client
     * 
     * @var \Predis_Client
     */
    protected $_client;

    /**
     * Configuration
     * 
     * @var array
     */
    protected $_lifetime;

    /**
     * Construct save handler
     *     
     * @param $params array, Predis_Client params
     */
    public function __construct($params = array())
    {   
        $this->_lifetime = (int)array_try_get($params, 'lifetime', ini_get('session.gc_maxlifetime'));

        if (!isset($params['cacheId']))
        {
            throw new \Zend_Session_SaveHandler_Exception('cacheId missing.');
        }
        
        $cacheId = $params['cacheId'];

        $this->_client = App::GetInstance()->cache($cacheId);

        if (!is_a($this->_client, 'Predis_Client'))
        {
            throw new \Zend_Session_SaveHandler_Exception('Invalid cacheId for RedisSessionSaveHandler.');
        }
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        Zend_Session::writeClose();        
    }

    /**
     * @inherited
     */
    public function open($savePath, $name)
    {
        return true;
    }

    /**
     * Close session
     *
     * @return boolean
     */
    public function close()
    {     
        //log is not allowed, the logger has already been destroyed
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $result = $this->_client->get($id);

        if (empty($result))
        {
            return '';
        }
        else
        {
            return $result;
        }
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $reply = $this->_client->set($id, $data);

        if ($reply) 
        {
            $this->_client->expire($id, $this->_lifetime);
        }
        else
        {
            App::GetInstance()->log()->err("write failed!. reply: {$reply}, data: " . var_export($data, true));
        }

        return $reply;
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {        
        $this->_client->delete($id);

        return true;
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {        
        return true;
    }
}