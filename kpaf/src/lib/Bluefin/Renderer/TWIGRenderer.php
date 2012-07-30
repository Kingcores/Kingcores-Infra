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

        /**
         * @var \Bluefin\Gateway $gateway
         */
        $gateway = App::getInstance()->getRegistry('gateway');
        if (isset($gateway))
        {
            $template = $gateway->parseRoutingValue($template);
        }

        $loader = new \Twig_Loader_Filesystem($view->getOption('root', APP_VIEW));
        $twig = new \Twig_Environment($loader, ENABLE_CACHE ? array('cache' => CACHE . '/view') : array());

        $filters = $view->getOption('filters', array());
        foreach ($filters as $filter => $function)
        {
            $twig->addFilter($filter, new \Twig_Filter_Function($function));
        }

        if (ENV == 'dev')
        {
            $twig->addFilter('dump', new \Twig_Filter_Function('var_dump'));
        }

        $functions = $view->getOption('functions', array());
        foreach ($functions as $functionName => $function)
        {
            $twig->addFunction($functionName, new \Twig_Function_Function($function));
        }

        $tests = $view->getOption('tests', array());
        foreach ($tests as $testName => $testFunction)
        {
            $twig->addTest($testName, new \Twig_Test_Function($testFunction));
        }
        
        $template = $twig->loadTemplate($template);
        $result = $template->render($view->getData());

        return $result;
    }
}