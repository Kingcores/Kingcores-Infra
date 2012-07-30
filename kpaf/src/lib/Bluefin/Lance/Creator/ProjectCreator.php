<?php

namespace Bluefin\Lance\Creator;

use Bluefin\Lance\ReportEntry;

class ProjectCreator extends CreatorBase
{
    public function create($params = null)
    {
        $report = array();

        if (file_exists(ROOT . '/project.lock'))
        {
            echo "The project has been locked. ";
            echo "It means the project has already been created. ";
            echo "If you want to continue, please delete 'project.lock' manually.";
        }
        else
        {
            $this->_createDirIfNotExist(APP_LIB, $report);
            $this->_createDirIfNotExist(APP_ETC, $report);
            $this->_createDirIfNotExist(APP_VIEW, $report);
            $this->_createDirIfNotExist(LANCE . '/schema', $report);
            $this->_createDirIfNotExist(LANCE . '/data', $report);
            $this->_createDirIfNotExist(LANCE . '/auth', $report);
            $this->_createDirIfNotExist(WEB_ROOT, $report);

            $this->_copyFileIfNotExist(APP_ETC . '/global.dev.yml', BLUEFIN_LANCE . '/templates/project/global.yml', $report);
            $this->_copyFileIfNotExist(APP_ETC . '/route/bluefin.yml', BLUEFIN_LANCE . '/templates/project/route_bluefin.yml', $report);
            $this->_copyFileIfNotExist(APP_ETC . '/route/default.yml', BLUEFIN_LANCE . '/templates/project/route_default.yml', $report);
            $this->_copyFileIfNotExist(APP_LIB . '/Sample/Controller/HomeController.php', BLUEFIN_LANCE . '/templates/project/HomeController.php', $report);
            $this->_copyFileIfNotExist(APP_VIEW . '/Sample/Home.index.html', BLUEFIN_LANCE . '/templates/project/Home.index.html', $report);
            $this->_copyFileIfNotExist(WEB_ROOT . '/index.php', BLUEFIN_LANCE . '/templates/project/index.php', $report);

            touch(ROOT . '/project.lock');
        }

        return $report;
    }
}
