<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;

class ProjectTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        $paramsArray = App::loadYmlFileEx($params);

        if (!array_key_exists('namespace', $paramsArray))
        {
            throw new \InvalidArgumentException("'namespace' should be given in 'params'.");
        }

        if (!array_key_exists('user', $paramsArray))
        {
            throw new \InvalidArgumentException("'user' should be given in 'params'.");
        }

        if (!array_key_exists('group', $paramsArray))
        {
            throw new \InvalidArgumentException("'group' should be given in 'params'.");
        }

        $namespace = $paramsArray['namespace'];
        $user = $paramsArray['user'];
        $group = $paramsArray['group'];

        $report = array();

        if (file_exists(ROOT . "/{$namespace}.project.lock"))
        {
            echo "The project has been locked. ";
            echo "It means the project has already been created. ";
            echo "If you want to continue, please delete '{$namespace}.project.lock' manually.";
        }
        else
        {
            $this->_createDirIfNotExist(APP_LIB . "/{$namespace}", $report);
            $this->_createDirIfNotExist(APP_ETC, $report);
            $this->_createDirIfNotExist(APP_VIEW, $report);
            $this->_createDirIfNotExist(LANCE . '/schema', $report);
            $this->_createDirIfNotExist(LANCE . '/data', $report);
            $this->_createDirIfNotExist(LANCE . '/auth', $report);
            $this->_createDirIfNotExist(WEB_ROOT, $report);
            $this->_createDirIfNotExist(ROOT . '/log', $report);
            $this->_createDirIfNotExist(CACHE, $report);

            $this->_copyFileIfNotExist(BLUEFIN_LANCE . '/templates/project/global.yml', APP_ETC . '/global.yml', $report);

            $this->_addPlaceHolder('app/etc/db/placeholder.yml', $report);
            $this->_addPlaceHolder('app/etc/auth/placeholder.yml', $report);

            $data = array('namespace' => $namespace);

            $this->_renderTemplate(
                "project/route_default.yml.twig",
                "app/etc/route/default.yml",
                $data,
                $report
            );

            $this->_renderTemplate(
                "project/HomeController.php.twig",
                "app/lib/{$namespace}/Controller/HomeController.php",
                $data,
                $report
            );

            $this->_copyFileIfNotExist(BLUEFIN_LANCE . '/templates/project/Home.index.html', APP_VIEW . "/{$namespace}/Home.index.html", $report);
            $this->_copyFileIfNotExist(BLUEFIN_LANCE . '/templates/project/env.php', ROOT . '/../env.php', $report);
            $this->_copyFileIfNotExist(BLUEFIN_LANCE . '/templates/project/index.php', WEB_ROOT . '/index.php', $report);

            exec_shell_command("chown -hR {$user}:{$group} " . ROOT);

            touch(ROOT . "/{$namespace}.project.lock");
        }

        $this->_logReport($report);
    }
}
