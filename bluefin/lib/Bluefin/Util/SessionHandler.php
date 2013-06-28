<?php

namespace Bluefin\Util;

use Bluefin\App;
use Bluefin\Log;
use Bluefin\Convention;

class SessionHandler
{
    protected $_persistence;
    protected $_prefix;

    public function __construct(array $options)
    {
        $cacheID = array_try_get($options, 'persistence', 'session');

        $this->_persistence = App::GetInstance()->cache($cacheID);

        //[+]DEBUG
        App::GetInstance()->log()->debug("Custom session handler based-on '{$cacheID}' cache persistence is constructed.", Log::CHANNEL_DIAG);
        //[-]DEBUG
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
    }

    /**
     * Open Session
     *
     * @param string $save_path
     * @param string $name
     * @return boolean
     */
    public function open($save_path, $name)
    {
        App::GetInstance()->log()->debug("Session '{$name}' is opened at '{$save_path}'.", Log::CHANNEL_DIAG);
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
        $result = $this->_persistence->get($id);

        //[+]DEBUG
        App::GetInstance()->log()->debug("Read session[{$id}]: {$result}", Log::CHANNEL_DIAG);
        //[-]DEBUG

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
        return $this->_persistence->set($id, $data);
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {        
        $this->_persistence->remove($id);
        return true;
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return boolean
     */
    public function gc($maxlifetime)
    {        
        return true;
    }
}