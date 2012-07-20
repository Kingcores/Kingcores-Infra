<?php

namespace Bluefin\Session;

class CacheStorage implements SaveHandlerInterface
{
    /**
     * Open Session - retrieve resources
     *
     * @param string $savePath
     * @param string $name
     */
    public function open($savePath, $name)
    {
        // TODO: Implement open() method.
    }

    /**
     * Close Session - free resources
     *
     */
    public function close()
    {
        // TODO: Implement close() method.
    }

    /**
     * Read session data
     *
     * @param string $id
     */
    public function read($id)
    {
        // TODO: Implement read() method.
    }

    /**
     * Write Session - commit data to resource
     *
     * @param string $id
     * @param mixed $data
     */
    public function write($id, $data)
    {
        // TODO: Implement write() method.
    }

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $id
     */
    public function destroy($id)
    {
        // TODO: Implement destroy() method.
    }

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime)
    {
        // TODO: Implement gc() method.
    }

}
