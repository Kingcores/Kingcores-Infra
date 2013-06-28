<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Arsenal;

class ShowFileTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        echo file_get_contents($params) . "\n";
    }
}
