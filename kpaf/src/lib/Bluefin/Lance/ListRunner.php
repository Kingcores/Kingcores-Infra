<?php

namespace Bluefin\Lance;

class ListRunner
{
    public function run($dbName, $filename)
    {
        $path = dirname($filename);
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $output = array();

        foreach ($lines as $line)
        {
            $file = build_path($path, $line);
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if (strcasecmp($ext, 'sql') == 0)
            {
                _SQL($dbName, $file);
                $output[] = $file;
            }
            else if (strcasecmp($ext, 'php') == 0)
            {
                include $file;
                $output[] = $file;
            }
        }

        return $output;
    }
}
