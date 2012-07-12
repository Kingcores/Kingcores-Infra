<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;
use Symfony\Component\Yaml\Yaml;

class YAMLRenderer implements RendererInterface
{
    public function render(View $view)
    {
        return Yaml::dump($view->getData(), 1);
    }
}
