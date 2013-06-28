<?php

namespace Bluefin\Lance\Task;

class ReInitTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        $report = array();

        $this->_deleteDirIfExist(APP, $report);
        $this->_deleteDirIfExist(CACHE, $report);
        $this->_deleteDirIfExist(WEB_ROOT, $report);
        $this->_deleteDirIfExist(ROOT . '/log', $report);

        $this->_deleteFiles(ROOT . '/*.project.lock', $report);

        $this->_logReport($report);
    }
}
