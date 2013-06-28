<?php

namespace Bluefin\Controller;

use Bluefin\App;
use Bluefin\Controller;
use Bluefin\Convention;

class ViewController extends Controller
{
    /**
     * 初始化控制器，在父类构造方法中调用。
     * @throws \Bluefin\Exception\ConfigException
     * @return void
     */
    protected function _init()
    {
        parent::_init();

        $routeRule = $this->_gateway->getRouteRule();

        if (!array_key_exists('view', $routeRule))
        {
            throw new \Bluefin\Exception\ConfigException("View is required for ViewController. Route: {$this->_gateway->getRouteName()}");
        }

        $this->_view->resetOptions($routeRule['view']);

        $dataSource = $this->_view->getOption('data');
        if (is_array($dataSource))
        {
            foreach ($dataSource as $key => $value)
            {
                $this->_view->set($key, $this->_gateway->parseRoutingValue($value));
            }
        }
    }
}
