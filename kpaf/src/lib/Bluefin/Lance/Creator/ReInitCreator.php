<?php

namespace Bluefin\Lance\Creator;

class ReInitCreator extends CreatorBase
{
    public function create($params = null)
    {
        $report = array();

        $this->_deleteDirIfExist(APP, $report);
        $this->_deleteDirIfExist(LANCE, $report);
        $this->_deleteDirIfExist(CACHE, $report);
        $this->_deleteDirIfExist(WEB_ROOT, $report);
        $this->_deleteDirIfExist(ROOT . '/log', $report);

        file_exists(ROOT . '/project.lock') && @unlink(ROOT . '/project.lock');

        return $report;
    }
}
