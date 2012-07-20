<?php

require_once "phing/Task.php";
require_once '../lib/Bluefin/bluefin.php';

class CreateTask extends Task {

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
        $className = "\\Bluefin\\Lance\\Creator\\" . usw_to_pascal($this->_type) . 'Creator';
        $creator = new $className;
        $creator->create();
    }
}