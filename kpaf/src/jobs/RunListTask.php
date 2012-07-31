<?php

require_once "phing/Task.php";
require_once '../lib/Bluefin/bluefin.php';

use Bluefin\App;
use Bluefin\Lance\ListRunner;

class RunListTask extends Task
{
    private $_dbName;
    private $_listName;

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
        $runner = new ListRunner();
        $result = $runner->run($this->_dbName, $this->_listName);

        foreach ($result as $line)
        {
            echo $line . "\n";
        }
    }
}
