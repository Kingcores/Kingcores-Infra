<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Arsenal;

class EnumTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        switch ($params)
        {
            case 'migration':
                $this->_enum4Migration();
                break;

            default:
                throw new \Bluefin\Exception\ConfigException("Invalid params for enum task!");
                break;
        }
    }

    protected function _enum4Migration()
    {
        $verFile = APP . '/ver.lock';

        if (!file_exists($verFile))
        {
            throw new \Bluefin\Exception\FileNotFoundException($verFile);
        }

        $currentVerNum = trim(file_get_contents($verFile));

        $all = glob(LANCE . "/patch/{$currentVerNum}-*.yml", GLOB_ERR);

        foreach ($all as $file)
        {
            $basename = basename($file, ".yml");
            $parts = explode('-', $basename);
            echo $parts[1] . "\n";
        }
    }
}
