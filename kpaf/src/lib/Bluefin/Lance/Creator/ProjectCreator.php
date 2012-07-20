<?php

namespace Bluefin\Lance\Creator;

class ProjectCreator
{
    public function create()
    {
        if (file_exists(ROOT . '/project.lock'))
        {
            echo "The project has been locked. ";
            echo "It means the project has already been created. ";
            echo "If you want to continue, please delete 'project.lock' manually.";

            return;
        }

        ensure_dir_exist(APP_LIB);
        ensure_dir_exist(APP_ETC);
        ensure_dir_exist(APP_VIEW);
        ensure_dir_exist(LANCE);
        ensure_dir_exist(WEB_ROOT);

        ensure_file_exist(APP_ETC . '/global.dev.yml', BLUEFIN_LANCE . '/templates/project/global.yml');
        ensure_file_exist(APP_ETC . '/route/bluefin.yml', BLUEFIN_LANCE . '/templates/project/route_bluefin.yml');
        ensure_file_exist(APP_ETC . '/route/default.yml', BLUEFIN_LANCE . '/templates/project/route_default.yml');
        ensure_file_exist(APP_LIB . '/Sample/Controller/HomeController.php', BLUEFIN_LANCE . '/templates/project/HomeController.php');
        ensure_file_exist(APP_VIEW . '/Sample/Home.default.yml', BLUEFIN_LANCE . '/templates/project/Home.index.html');
        ensure_file_exist(WEB_ROOT . '/index.php', BLUEFIN_LANCE . '/templates/project/index.php');

        touch(ROOT . '/project.lock');
    }
}
