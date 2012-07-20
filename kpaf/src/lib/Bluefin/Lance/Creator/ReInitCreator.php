<?php

namespace Bluefin\Lance\Creator;

class ReInitCreator
{
    public function create()
    {
        del_dir(APP);
        del_dir(LANCE);
        del_dir(CACHE);
        del_dir(WEB_ROOT);

        file_exists(ROOT . '/project.lock') && unlink(ROOT . '/project.lock');
    }
}
