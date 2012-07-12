<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;

class JSONRenderer implements RendererInterface
{
    public function render(View $view)
    {
        /*
        App::getInstance()->response()
            ->setHeader("Content-Type", "application/json", true)
            ->setHeader("Cache-Control", "no-store", true);
        */
        return json_encode_cn($view->getData());
    }
}
