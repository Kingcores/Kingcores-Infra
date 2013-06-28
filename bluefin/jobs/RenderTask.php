<?php

require_once "phing/Task.php";
require_once '../lib/Bluefin/bluefin.php';

use Bluefin\App;
use Bluefin\Lance\FileRenderer;

class RenderTask extends Task
{

    /**
     * The from passed in the buildfile.
     */
    private $_from = null;

    /**
     * The setter for the attribute "from"
     */
    public function setFrom($str) {
        $this->_from = $str;
    }

    /**
     * The to passed in the buildfile.
     */
    private $_to = null;

    /**
     * The setter for the attribute "to"
     */
    public function setTo($str) {
        $this->_to = $str;
    }

    /**
     * The with passed in the buildfile.
     */
    private $_with = null;

    /**
     * The setter for the attribute "with"
     */
    public function setWith($str) {
        $this->_with = $str;
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

        $inputs = file(ROOT . '/jobs/' . $this->_with);
        $data = array();

        foreach ($inputs as $line)
        {
            $line = trim($line);
            if ($line[0] == '#') continue;

            $parts = explode('=', $line, 2);

            $key = usw_to_camel(str_replace('.', '_', $parts[0]));

            $data[$key] = trim($parts[1]);
        }

        FileRenderer::render($this->_from, $this->_to, $data);
    }
}