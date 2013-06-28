<?php

namespace Bluefin\Lance;

use Symfony\Component\Yaml\Yaml;
use Bluefin\View;
use Bluefin\Lance\Convention;

class FileRenderer
{
    /**
     * @static
     * @param string $template Twig template located in /lib/Bluefin/Lance/templates or in /extend/templates
     * @param string $target Target file relative to ROOT
     * @param array $data
     * @param bool $append
     */
    public static function render($template, $target, array $data, $append = false)
    {
        $templateRoot = Convention::getTemplateRoot($template);

        $view = new View(
            array(
                'renderer' => 'twig',
                'root' => $templateRoot,
                'template' => $template,
                'filters' => [
                    'export' => '\Bluefin\Lance\Convention::dumpValue',
                    'pascal' => 'usw_to_pascal',
                    'camel' => 'usw_to_camel',
                    'const' => 'usw_to_const',
                    'words' => 'usw_to_words',
                    'values' => 'array_values',
                    'quote' => 'str_quote',
                    'pad_lines' => 'str_pad_lines',
                    'typename' => '\Bluefin\Lance\PHPCodingLogic::getPhpTypeName'
                ],
                'functions' => [
                    'settext' => '\Bluefin\Lance\Convention::addMetadataTranslation',
                    'in_array' => 'in_array'
                ]
            )
        );

        $view->resetData($data);
        $content = $view->render();

        $filename = ROOT . "/{$target}";

        if (file_exists($filename) && $append)
        {
            $original = Yaml::load($filename);
            $new = Yaml::load($content);

            $updated = array_merge_recursive($original, $new);
            $content = Yaml::dump($updated, 4);
        }

        $dir = dirname($filename);
        ensure_dir_exist($dir);

        file_put_contents($filename, $content, LOCK_EX);
    }
}
