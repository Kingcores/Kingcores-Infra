<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;

class RawRenderer implements RendererInterface
{
    public function render(View $view)
    {
        $headers = $view->getOption('headers');
        $response = App::getInstance()->response();

        foreach ($headers as $header => $value)
        {
            $response->setHeader($header, $value, true);
        }

        return $view->getData();
    }
}
