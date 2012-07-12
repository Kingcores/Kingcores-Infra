<?php

namespace Bluefin\Renderer;

use Bluefin\View;

interface RendererInterface
{
    /**
     * @abstract
     * @param  $data
     * @param  $viewName
     * @return string
     */
    public function render(View $view);
}
