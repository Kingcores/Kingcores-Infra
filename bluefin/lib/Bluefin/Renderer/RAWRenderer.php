<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;

class RawRenderer implements RendererInterface
{
    public function render(View $view)
    {
        return $view->getData();
    }
}
