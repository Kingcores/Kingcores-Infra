<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class XMLRenderer implements RendererInterface
{
    public function render(View $view)
    {    
        $serializer = new Serializer();
        $serializer->setEncoder('xml', new XmlEncoder());
        return $serializer->encode($view->getData(), 'xml');
    }
}
