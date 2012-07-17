<?php

class ProjectCreator
{
    public function create()
    {
        ensure_dir_exist(APP);
        ensure_dir_exist(LANCE);
        ensure_dir_exist(WEB_ROOT);

        ensure_file_exist(APP_ETC . '/global.dev.php', BLUEFIN_LANCE . '/templates/project/global.yml');
        ensure_file_exist(APP_ETC . '/route/bluefin.yml', BLUEFIN_LANCE . '/templates/project/route_bluefin.yml');
        ensure_file_exist(WEB_ROOT . '/index.php', BLUEFIN_LANCE . '/templates/project/index.php');
    }
}
