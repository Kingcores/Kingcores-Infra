<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;

class TWIGRenderer implements RendererInterface
{
    public function render(View $view)
    {
        $template = $view->getOption('template');

        if (!isset($template))
        {
            throw new \Bluefin\Exception\ConfigException("Template property is required by TWIGRenderer.");
        }

        $template = \Bluefin\VarText::parseVarText($template);

        $loader = new \Twig_Loader_Filesystem($view->getOption('root', APP_VIEW));
        $twig = new \Twig_Environment($loader, ENABLE_CACHE ? array('cache' => CACHE . '/view') : array());

        $twig->addFilter('base64', new \Twig_Filter_Function('base64_encode'));
        $twig->addFilter('json', new \Twig_Filter_Function('json_encode'));
        $twig->addFilter('hex', new \Twig_Filter_Function('bin2hex'));

        $filters = $view->getOption('filters');
        if (!empty($filters))
        {
            foreach ($filters as $filter => $function)
            {
                $twig->addFilter($filter, new \Twig_Filter_Function($function));
            }
        }

        $twig->addFunction('context', new \Twig_Function_Function('_C'));
        $twig->addFunction('text', new \Twig_Function_Function('_T'));
        $twig->addFunction('route', new \Twig_Function_Function('_R'));
        $twig->addFunction('my_url', new \Twig_Function_Function('_U'));
        $twig->addFunction('path', new \Twig_Function_Function('_P'));
        $twig->addFunction('url', new \Twig_Function_Function('build_uri'));

        if (ENV == 'dev')
        {
            $twig->addFunction('dump', new \Twig_Function_Function('var_dump'));
        }

        $functions = $view->getOption('functions');
        if (!empty($functions))
        {
            foreach ($functions as $functionName => $function)
            {
                $twig->addFunction($functionName, new \Twig_Function_Function($function));
            }
        }

        $tests = $view->getOption('tests');
        if (!empty($tests))
        {
            foreach ($tests as $testName => $testFunction)
            {
                $twig->addTest($testName, new \Twig_Test_Function($testFunction));
            }
        }
        
        $template = $twig->loadTemplate($template);
        $result = $template->render($view->getData());

        return $result;
    }
}