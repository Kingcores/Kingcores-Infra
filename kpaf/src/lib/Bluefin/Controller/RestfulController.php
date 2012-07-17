<?php

namespace Bluefin\Controller;

use Bluefin\App;
use Bluefin\Common;
use Bluefin\Convention;

class RestfulController extends ServiceController
{
    public function invokeAction()
    {
        $routeRule = $this->_gateway->getRouteRule();

        $viewOption = array_key_exists('view', $routeRule) ?
            $routeRule['view'] :
            array(
                'renderer' => 'json',
                'dataSource' => 'result'
            );

        $this->_view->resetOptions($viewOption);

        $args = $this->_request->getRouteParams();
        $keys = array_keys($args);
        if (count($keys) > 1)
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $fullName = $keys[0];
        $parts = explode('.', $fullName);
        $className = usw_to_pascal(array_pop($parts));

        foreach ($parts as &$part)
        {
            $part = usw_to_pascal($part);
        }

        $path = implode('/', $parts);

        $arg = $args[$keys[0]];

        $serviceArgs = array();
        $methodName = $this->_getMethod($arg, $serviceArgs);

        //[+]DEBUG
        $this->_logger->debug("Dispatching to service \"{$className}.{$methodName}\" ...");
        //[-]DEBUG

        $this->_dispatchRequest($path, $className, $methodName, $serviceArgs);
    }

    private function _getMethod($argValue, array &$serviceArgs)
    {
        $httpMethod = $this->_request->isPut() ? Common::HTTP_METHOD_PUT
                : ($this->_request->isDelete() ? Common::HTTP_METHOD_DELETE : $this->_request->getMethod());

        switch ($httpMethod)
        {
            case Common::HTTP_METHOD_GET:
                if (isset($argValue))
                {
                    $method = 'retrieve';
                    $serviceArgs[] = $argValue;
                }
                else
                {
                    $method = 'search';
                    $serviceArgs[] = $this->_request->getQueryParams();
                }

                break;

            case Common::HTTP_METHOD_POST:
                if (isset($argValue))
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }

                $method = 'create';
                $serviceArgs[] = $this->_request->getPostParams();
                break;

            case Common::HTTP_METHOD_PUT:
                if (!isset($argValue))
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }

                $method = 'update';
                $serviceArgs[] = $argValue;
                $serviceArgs[] = $this->_request->getPostParams();
                break;

            case Common::HTTP_METHOD_DELETE:
                if (!isset($argValue))
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }
                    
                $method = 'delete';
                $serviceArgs[] = $argValue;
                break;

            default:
                throw new \Bluefin\Exception\RequestException(null, Common::HTTP_METHOD_NOT_ALLOWED);
        }

        return $method;
    }
}
