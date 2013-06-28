<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Arsenal;

class CheckVersionTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        $verFile = APP . '/ver.lock';

        if (!file_exists($verFile))
        {
            throw new \Bluefin\Exception\FileNotFoundException($verFile);
        }

        $currentVerNum = trim(file_get_contents($verFile));
        $targetVerNum = trim($params);

        $patchFile = LANCE . "/patch/{$currentVerNum}-{$targetVerNum}.yml";

        if (!file_exists($patchFile))
        {
            throw new \Bluefin\Exception\FileNotFoundException($patchFile);
        }
    }
}
