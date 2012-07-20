<?php

namespace Bluefin\Yaml;

class Yaml
{
    /**
     * Loads a YAML file or a YAML string into a PHP array.
     *
     * @static
     * @param $input
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function load($input)
    {
        if (strpos($input, "\n") === false && is_file($input) && is_readable($input))
        {
            $input = file_get_contents($input, LOCK_EX);
        }

        $yaml = new Parser();

        try
        {
            $ret = $yaml->parse($input);
        }
        catch (\Exception $e)
        {
            throw new \InvalidArgumentException("Unable to parse YAML: {$e->getMessage()}", 0, $e);
        }

        return $ret;
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * @static
     * @param $array
     * @param int $inline
     * @return string
     */
    public static function dump($array, $inline = 2)
    {
        $yaml = new Dumper();

        return $yaml->dump($array, $inline);
    }
}
