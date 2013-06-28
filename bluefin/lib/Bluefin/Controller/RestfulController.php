<?php

namespace Bluefin\Controller;

use Bluefin\App;
use Bluefin\Common;
use Bluefin\Convention;

class RestfulController extends ServiceController
{
    protected function _getMethod(array &$args)
    {
        if (empty($args))
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $serviceName = array_shift($args);
        $parts = explode('.', $serviceName);
        $className = usw_to_pascal(array_pop($parts)) . 'Service';

        foreach ($parts as &$part)
        {
            $part = usw_to_pascal($part);
        }

        $path = empty($parts) ? '' : implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;

        $httpMethod = $this->_request->isPut() ? Common::HTTP_METHOD_PUT
                : ($this->_request->isDelete() ? Common::HTTP_METHOD_DELETE : $this->_request->getMethod());

        $numArgs = count($args);

        switch ($httpMethod)
        {
            case Common::HTTP_METHOD_GET:
                $method = 'get';
                $args = [$this->_regulateArgs($numArgs, $args)];
                break;

            case Common::HTTP_METHOD_POST:
                if ($numArgs == 0)
                {
                    $method = 'create';
                }
                else if ($numArgs == 2)
                {
                    $method = 'do' . usw_to_pascal($args[0]);
                    $args = [ $args[1] ];
                }
                else
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }
                break;

            case Common::HTTP_METHOD_PUT:
                $method = 'update';
                if ($numArgs != 1)
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }
                $args = [$args[0], $this->_request->getPostParams()];
                break;

            case Common::HTTP_METHOD_DELETE:
                $method = 'delete';
                if ($numArgs != 1)
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }
                break;

            default:
                throw new \Bluefin\Exception\RequestException(null, Common::HTTP_METHOD_NOT_ALLOWED);
        }

        return [$path, $className, $method];
    }

    protected function _regulateArgs($numArgs, array $args)
    {
        if ($numArgs % 2 != 0)
        {
            $args[] = null;
            $numArgs++;
        }

        $serviceArgs = [];
        for ($i = 0; $i < $numArgs; $i+=2)
        {
            $serviceArgs[$args[$i]] = $args[$i+1];
        }

        return $serviceArgs;
    }
}
