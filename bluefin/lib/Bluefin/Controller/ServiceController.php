<?php

namespace Bluefin\Controller;

use Bluefin\App;
use Bluefin\Gateway;
use Bluefin\Controller;
use Bluefin\Convention;

class ServiceController extends Controller
{
    protected $_classPath;

    public function invokeAction()
    {
        // extract view info
        $rules = $this->_gateway->getRouteRule();
        $viewInfo = array_try_get($rules, 'view', [
                        'renderer' => 'json',
                        'dataSource' => 'result'
                    ]);

        $this->_view->resetOptions($viewInfo);

        $args = $this->_request->getRouteParams();
        list($path, $className, $methodName) = $this->_getMethod($args);

        //[+]DEBUG
        $this->_logger->debug("Dispatching to service \"{$className}.{$methodName}\" ...");
        //[-]DEBUG

        $this->_dispatchRequest($path, $className, $methodName, $args);
    }

    public function preDispatch()
    {
    }

    public function postDispatch()
    {
    }

    protected function _getMethod(array &$args)
    {
        foreach ($args as $key => $value)
        {
            if (!is_int($key))
            {
                unset($args[$key]);
            }
        }

        // extract service name
        $service = $this->_gateway->getRuleProperty('service');

        if (!isset($service))
        {
            throw new \Bluefin\Exception\ConfigException("Service is required for ServiceController. Route: {$this->_gateway->getRouteName()}");
        }

        $parts = Gateway::splitDispatchTarget($service, true);

        if (empty($parts[0]))
        {
            $parts[0] = '';
        }
        else
        {
            $tempPath = '';
            $pathParts = explode('.', $parts[0]);
            foreach ($pathParts as $part)
            {
                $tempPath .= usw_to_pascal($part) . DIRECTORY_SEPARATOR;
            }

            $parts[0] = $tempPath;
        }

        $parts[1] = usw_to_pascal($parts[1]) . 'Service';
        $parts[2] = usw_to_camel($parts[2]);

        return $parts;
    }

    protected function _dispatchRequest($path, $className, $methodName, array $args)
    {
        if (is_null($methodName))
        {
            throw new \Bluefin\Exception\ConfigException("Missing \"methodName\" of service. Route: {$this->_gateway->getRouteName()}");
        }

        $serviceFile = APP_SERVICE . DIRECTORY_SEPARATOR . $path . $className . '.php';

        if (!file_exists($serviceFile))
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        require_once $serviceFile;
        $fullClassName = '\\' . $className;
        $object = new $fullClassName();

        try
        {
            $output = call_user_func_array(array(&$object, $methodName), $args);
            if (isset($output))
            {
                $this->_view->set('result', $output);
            }
        }
        catch (\Bluefin\Exception\RequestException $re)
        {
            $this->_response->setHttpResponseCode($re->getCode());
            $result = ['errorno' => $re->getCode(),
                             'request' => $this->_request->getRequestUri(),
                             'error' => $re->getMessage()];
            if (RENDER_EXCEPTION)
            {
                $result['trace'] = $re->getTraceAsString();
            }
            $this->_view->set('result', $result);
        }
        catch (\Exception $e)
        {
            $this->_response->setHttpResponseCode(\Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR);
            $errorno = $e->getCode();
            $this->_view->set('result',
                ['errorno' => (isset($errorno) ? $errorno : \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR),
                 'request' => $this->_request->getRequestUri(),
                 'error' => \Bluefin\Common::getStatusCodeMessage(\Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR)]);

            App::getInstance()->log()->error('Server Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
