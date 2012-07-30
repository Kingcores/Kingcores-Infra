<?php

namespace Bluefin\Lance;

use Bluefin\View;
use Bluefin\Lance\Convention;

class FileRenderer
{
    /**
     * @static
     * @param string $template Twig template located in /lib/Bluefin/Lance/templates or in /extend/templates
     * @param string $target Target file relative to ROOT
     * @param array $data
     */
    public static function render($template, $target, array $data)
    {
        $templateRoot = Convention::getTemplateRoot($template);

        $view = new View(
            array(
                'renderer' => 'twig',
                'root' => $templateRoot,
                'template' => $template,
                'filters' => array(
                    'export' => '\Bluefin\Lance\Convention::dumpValue',
                    'pascal' => 'usw_to_pascal',
                    'camel' => 'usw_to_camel',
                    'const' => 'usw_to_const',
                    'trim' => 'trim',
                    'values' => 'array_values'
                )
            )
        );

        $view->resetData($data);
        $content = $view->render();

        $filename = ROOT . "/{$target}";

        $dir = dirname($filename);
        ensure_dir_exist($dir);

        file_put_contents($filename, $content, LOCK_EX);
    }
}
