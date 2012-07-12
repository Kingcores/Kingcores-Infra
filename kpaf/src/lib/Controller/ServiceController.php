<?php

namespace Bluefin\Controller;

use Bluefin\App;
use Bluefin\Controller;
use Bluefin\Convention;

class ServiceController extends Controller
{
    public function invokeAction()
    {
        $routeRule = $this->_gateway->getRouteRule();

        // extract service name
        $service = $this->_gateway->getRuleProperty('service', $routeRule);

        if (!isset($service))
        {
            throw new \Bluefin\Exception\ConfigException("Service is required for ServiceController. Route: {$this->_gateway->getRouteName()}");
        }

        // extract view info
        if (!array_key_exists('view', $routeRule))
        {
            throw new \Bluefin\Exception\ConfigException("View is required for ServiceController. Route: {$this->_gateway->getRouteName()}");
        }

        $this->_view->resetOptions($routeRule['view']);

        // extract arguments
        $args = array();
        $argsInRule = array_try_get($routeRule, 'args');

        if (isset($argsInRule))
        {
            foreach ($argsInRule as $arg)
            {
                if (is_dot_name($arg))
                {
                    $args[] = _CONTEXT($arg);
                }
                else
                {
                    $args[] = $arg;
                }
            }
        }
        else
        {
            $i = 0;
            while ($arg = $this->_request->get("{$i}"))
            {
                $args[] = $arg;
                $i++;
            }
        }

        list($path, $className, $methodName) = $this->_gateway->splitDispatchTarget($service, true);

        //[+]DEBUG
        $this->_logger->debug("Dispatching to service \"{$service}\" ...");
        //[-]DEBUG

        $this->_dispatchRequest($path, $className, $methodName, $args);
    }

    protected function _dispatchRequest($path, $className, $methodName, array $args)
    {
        $className .= 'Service';

        if (is_null($methodName))
        {
            throw new \Bluefin\Exception\ConfigException("Missing \"methodName\" of service. Route: {$this->_gateway->getRouteName()}");
        }

        $serviceFile = APP_SERVICE . '/' . ($path ? $path . '/' : '') . $className . '.php';

        if (!file_exists($serviceFile))
        {
            throw new \Bluefin\Exception\PageNotFoundException(_U());
        }

        require_once $serviceFile;
        $fullClassName = '\\' . $className;
        $object = new $fullClassName;

        $filterArgs = array($methodName);

        if (call_user_func_array(array(&$object, 'beforeFilter'), $filterArgs))
        {
            $output = call_user_func_array(array(&$object, $methodName), $args);
            if (isset($output))
            {
                $this->_view->set('result', $output);
            }
        }
        else
        {
            throw new \Bluefin\Exception\UnauthorizedException();
        }
    }
}
