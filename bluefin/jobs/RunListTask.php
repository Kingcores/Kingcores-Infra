<?php

require_once "phing/Task.php";
require_once '../lib/Bluefin/bluefin.php';

use Bluefin\App;
use Bluefin\Lance\ListRunner;

class RunListTask extends Task
{
    private $_lanceName;
    private $_dbName;
    private $_dbHost;
    private $_dbPort;
    private $_dbCharset;
    private $_dbUser;
    private $_dbPass;
    private $_listName;

    public function setLanceName($lanceName)
    {
        $this->_lanceName = $lanceName;
    }

    public function setDbName($dbName)
    {
        $this->_dbName = $dbName;
    }

    public function setListName($listName)
    {
        $this->_listName = $listName;
    }

    public function init()
    {

    }

    public function main()
    {
        $config = array(
            'lance' => $this->_lanceName,
            'host' => $this->_dbHost,
            'port' => $this->_dbPort,
            'username' => $this->_dbUser,
            'password' => $this->_dbPass,
            'dbname' => $this->_dbName,
            'charset' => $this->_dbCharset
        );

        $runner = new ListRunner($config);
        $runner->run($this->_listName);
    }

    public function setDbCharset($dbCharset)
    {
        $this->_dbCharset = $dbCharset;
    }

    public function setDbHost($dbHost)
    {
        $this->_dbHost = $dbHost;
    }

    public function setDbPass($dbPass)
    {
        $this->_dbPass = $dbPass;
    }

    public function setDbPort($dbPort)
    {
        $this->_dbPort = $dbPort;
    }

    public function setDbUser($dbUser)
    {
        $this->_dbUser = $dbUser;
    }
}
