<?php

require_once "phing/Task.php";
require_once '../lib/Bluefin/bluefin.php';

use Bluefin\Lance\ReportEntry;

class LanceTask extends Task
{

    /**
     * The type passed in the buildfile.
     */
    private $_type = null;

    /**
     * The setter for the attribute "type"
     */
    public function setType($str) {
        $this->_type = $str;
    }

    private $_params = null;

    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * The init method: Do init steps.
     */
    public function init() {
      // nothing to do here
    }

    /**
     * The main entry point method.
     */
    public function main() {
        $className = "\\Bluefin\\Lance\\Task\\" . usw_to_pascal($this->_type) . 'Task';

        /**
         * @var \Bluefin\Lance\Task\TaskInterface $task
         */
        $task = new $className();
        $task->execute($this->_params);
    }
}