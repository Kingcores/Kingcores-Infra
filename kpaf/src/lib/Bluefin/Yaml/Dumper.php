<?php

namespace Bluefin\Yaml;

class Dumper
{
    /**
     * Dumps a PHP value to YAML.
     *
     * @param  mixed   $input  The PHP value
     * @param  integer $inline The level where you switch to inline YAML
     * @param  integer $indent The level of indentation (used internally)
     *
     * @return string  The YAML representation of the PHP value
     */
    public function dump($input, $inline = 0, $indent = 0)
    {
        $output = '';
        $prefix = $indent ? str_repeat(' ', $indent) : '';

        if ($inline <= 0 || !is_array($input) || empty($input)) {
            $output .= $prefix.Inline::dump($input);
        } else {
            $isAHash = array_keys($input) !== range(0, count($input) - 1);

            foreach ($input as $key => $value) {
                $willBeInlined = $inline - 1 <= 0 || !is_array($value) || empty($value);

                $output .= sprintf('%s%s%s%s',
                    $prefix,
                    $isAHash ? Inline::dump($key).':' : '-',
                    $willBeInlined ? ' ' : "\n",
                    $this->dump($value, $inline - 1, $willBeInlined ? 0 : $indent + 2)
                ).($willBeInlined ? "\n" : '');
            }
        }

        return $output;
    }
}
