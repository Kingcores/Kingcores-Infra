<?php

namespace Bluefin;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Exception\ForwardException;
use Bluefin\Exception\RedirectException;
use Bluefin\Exception\RequestException;
use Bluefin\Exception\ServerErrorException;
use Exception;

/**
 * Gateway
 *
 * 查找能够匹配url的路由，在确定根据查找到的路由设置时，请求的方法存在后，将module，
 * controller，action保存到app，否则根据设置确定继续寻找其它路由或终止。确定路由后，
 * 获取routing中设置的参数并做验证，然后执行请求
 *
 */
class Gateway
{
    const DOT_NAME_PATTERN = '/\{(\w+\.\w+(?:\|[^|}]+)*)\}/';
    const MAX_FORWARD_LIMIT = 3;

    /**
     * HTTP请求
     * @var Request
     */
    private $_request;

    private $_response;

    private $_namespace;

    private $_moduleName;

    private $_controllerName;

    private $_actionName;
    // 路由名称
    private $_routeName;
    // 路由项，数组
    private $_routeRule;

    private $_controllerClassName;

    /**
     * @var \Bluefin\Controller
     */
    private $_controller;

    /**
     * @var bool
     */
    private $_bypassAction;

    /**
     * @var array
     */
    private $_cache;

    private $_routingTable;

    private $_requestRoute;

    private $_serverUrlRewritable;

    private $_dispatchedTimes;

    public function __construct()
    {              
        $this->_request = App::getInstance()->request();
        $this->_response = App::getInstance()->response();
        $this->_bypassAction = false;
        $this->_cache = array();
        $this->_routingTable = _C('routing', array());
        $this->_serverUrlRewritable = _C('app.serverUrlRewritable', false);
        $this->_dispatchedTimes = 0;

        App::getInstance()->setRegistry('gateway', $this);
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function getRouteName()
    {
        return $this->_routeName;
    }

    public function getRouteRule()
    {
        return $this->_routeRule;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * 返回模块名
     * @return string
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * 返回控制器名
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * 返回动作名
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    public function getRuleProperty($paramName, array $rule = null)
    {
        if (!isset($rule))
        {
            $rule = $this->_routeRule;
        }

        $paramInRule = array_try_get($rule, $paramName);

        if (isset($paramInRule))
        {
            return $this->parseRoutingValue($paramInRule);
        }

        return null;
    }

    public function parseRoutingValue($value)
    {
        return preg_replace_callback(
            self::DOT_NAME_PATTERN,
            array(&$this, '_ruleDotNameMatchCallback'),
            $value
        );
    }

    public function splitDispatchTarget($name, $asPath = false)
    {
        $pos = strrpos($name, '.');

        if (false === $pos)
        {
            $body = $name;
            $tail = null;
        }
        else
        {
            $body = substr($name, 0, $pos);
            $tail = substr($name, $pos+1);
        }

        if ($asPath)
        {
            $body = str_replace("\\", '/', $body);
            $delimiter = '/';
        }
        else
        {
            $body = str_replace('/', "\\", $body);
            $delimiter = "\\";
        }

        $pos = strrpos($body, $delimiter);
        if (false === $pos)
        {
            $head = null;
        }
        else
        {
            $head = substr($body, 0, $pos);
            $body = substr($body, $pos+1);
        }
        
        return array($head, $body, $tail);
    }

    /**
     * 将形如“<模块名>/<控制器名或服务名>.<动作名或方法名>”的全称分解。
     * @param $dispatchTargetName 控制器或服务合并全称
     * @return array 0 => 模块名, 1 => 控制器名或服务名, 2 => 动作名或方法名
     */
    public function parseDispatchTargetName($dispatchTargetName)
    {
        $result = array();
        $parts = explode('\\', $dispatchTargetName);

        $dispatchTargetName = array_pop($parts);

        if (empty($parts))
        {
            $result[0] = null;
        }
        else
        {
            $result[0] = implode('\\', $parts);
        }

        $parts = explode('.', $dispatchTargetName, 2);

        if (count($parts) > 1)
        {
            $result[1] = $parts[0];
            $result[2] = $parts[1];
        }
        else
        {
            $result[1] = $parts[0];
            $result[2] = null;
        }

        return $result;
    }

    /**
     * HTTP请求服务入口
     */
    public function service()
    {
        $errorCode = 0;
        $message = '';

        try
        {
            $this->_runService();
        }
        catch (RedirectException $redirEx)
        {
            //[+]DEBUG
            App::getInstance()->log()->debug("Redirected to \"{$redirEx->getMessage()}\".");
            //[-]DEBUG
        }
        catch (RequestException $reqEx)
        {
            //转到请求错误页
            $this->_response->setException($reqEx);
            $errorCode = $reqEx->getCode();
            $message = $reqEx->getMessage();

            App::getInstance()->log()->notice('Request Exception: ' . $reqEx->getMessage());
        }
        catch (ServerErrorException $srvEx)
        {
            //转到服务器错误页
            $this->_response->setException($srvEx);
            $errorCode = $srvEx->getCode();
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);

            App::getInstance()->log()->alert(
                'Server Error: ' . $srvEx->getMessage() . "\n" . $srvEx->getTraceAsString()
            );
        }
        catch (Exception $e)
        {
            $this->_response->setException($e);
            $errorCode = \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE;
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);

            App::getInstance()->log()->err('Unknown Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        if (0 !== $errorCode)
        {
            $this->_response->setHttpResponseCode($errorCode);

            if (RENDER_EXCEPTION)
            {
                $this->_response->renderExceptions(true);
            }
            else
            {
                $this->_response->setBody($message);
            }
        }

        $this->_response->sendResponse();

        $passedTime = App::getInstance()->end();
        App::getInstance()->log()->info('Execution time: ' . $passedTime . '(s).');
    }

    public function forward($actionName, $controllerName = null, $moduleName = null, $namespace = null)
    {
        isset($namespace) || ($namespace = $this->_namespace);
        isset($moduleName) || ($moduleName = $this->_moduleName);
        isset($controllerName) || ($controllerName = $this->_controllerName);

        throw new \Bluefin\Exception\ForwardException($namespace, $moduleName, $controllerName, $actionName);
    }

    public function redirect($uri, $code = Common::HTTP_FOUND)
    {
        $this->_response->setRedirect($uri, $code);
        throw new \Bluefin\Exception\RedirectException($uri);
    }

    public function url($routeName = null, $params = null, array $queryParams = null, array $fragmentParams = null)
    {
        if (!isset($routeName) && !isset($params))
        {
            isset($queryParams) || $queryParams = $this->_request->getQueryParams();
        }

        return $this->_request->getScheme() . '://' . $this->_request->getHttpHost() . $this->relUrl($routeName, $params, $queryParams, $fragmentParams);
    }

    public function relUrl($routeName = null, $params = null, array $queryParams = null, array $fragmentParams = null)
    {
        if (isset($routeName))
        {
            App::assert(array_key_exists($routeName, $this->_routingTable));
            $routeRule = $this->_routingTable[$routeName];
        }
        else
        {
            if (!isset($params))
            {
                $uri = $this->_request->getRelativePath() . str_pad_if($this->_requestRoute, '/', true, false);
                return build_uri($uri, $queryParams, $fragmentParams);
            }
            $routeRule = $this->_routeRule;
        }

        $route = $routeRule['route'];

        if (strpos($route, '/:') === false)
        {
            $uri = $this->_request->getRelativePath() . $route;
        }
        else
        {
            is_array($params) || ($params = array($params));

            $oldParams = $this->_request->getRouteParams();
            $len = count($params);

            $params = array_reverse($params);

            $params2 = array();

            $parts = explode('/:', $route);
            array_shift($parts);
            $parts = array_reverse($parts);

            $i = 0;
            foreach ($parts as $token)
            {
                $pos = strpos($token, '/');
                if ($pos !== false) $token = substr($token, 0, $pos);

                if ($i < $len)
                {
                    $params2[":{$token}"] = $params[$i++];
                }
                else
                {
                    App::assert(array_key_exists($token, $oldParams), "Missing value for route parameter [{$token}].");
                    $params2[":{$token}"] = $oldParams[$token];
                }
            }

            $route = strtr($route, $params2);                    

            if ($this->_serverUrlRewritable)
            {
                $uri = $this->_request->getRelativePath() . str_pad_if($route, '/', true, false);
            }
            else
            {
                $uri = $this->_request->getRelativeLandingUrl();
                isset($queryParams) || ($queryParams = array());

                $queryParams[Convention::KEYWORD_REQUEST_ROUTE] = $route;
            }
        }

        return build_uri($uri, $queryParams, $fragmentParams);
    }

    private function _runService()
    {
        $requestRoute = $this->_request->getQueryParam(Convention::KEYWORD_REQUEST_ROUTE, '', true);
        $this->_requestRoute = isset($requestRoute) ? trim($requestRoute, '/') : '';

        if (false !== strpos($this->_requestRoute, ':') || !$this->_findRoute())
        {
            throw new \Bluefin\Exception\PageNotFoundException($this->_request->getRequestUri());
        }

        //[+]DEBUG
        App::getInstance()->log()->debug("Found route: {$this->_routeName}");
        //[-]DEBUG

        if ($this->_processRouteRule())
        {
            while (!$this->_dispatchRequest()) {}
        }
    }

    /**
     * 选择合适的路由，匹配到的路由项名称到$_selectedRoute
     * 找到合适的路由时返回true，否则返回false
     * @return bool
     * @throws Exception\ConfigException
     */
    private function _findRoute()
    {
        $replace = array('/' => '\/', '*' => '[\S]*');

        foreach ($this->_routingTable as $key => $val)
        {
            if (!isset($val['route']))
            {
                throw new \Bluefin\Exception\ConfigException(
                    "The route property of the routing item \"{$key}\" does not exist. Route is ignored."
                );
            }

            $definedURL = trim($val['route'], '/');

            if ($definedURL == $this->_requestRoute)
            {
                $this->_setRoute($key, $val, $this->_requestRoute, $definedURL);
                return true;
            }

            $translatedURL = strtr($definedURL, $replace);

            $preg = '/^' . preg_replace('/:\w+/', '\w+', $translatedURL) . '$/';

            if (preg_match($preg, $this->_requestRoute))
            {
                $this->_setRoute($key, $val, $this->_requestRoute, $definedURL);
                return true;
            }
        }

        return false;
    }

    private function _setRoute($routeKey, $routeItem, $queryString, $routeUrl)
    {
        $this->_routeName = $routeKey;
        $this->_routeRule = $routeItem;

        $routeParams = array();

        $queryArray = explode('/', $queryString);
        $definedArray = explode('/', $routeUrl);

        $i = 0;
        $iMax = count($definedArray);
        $jMax = count($queryArray);
        $cachedName = null;

        for ($j = 0; $j < $jMax; $j++)
        {
            if ($i >= $iMax)
            {
                throw new \Bluefin\Exception\ConfigException("Invalid config for route \"{$this->_routeName}\": {$routeUrl}");
            }

            $routePart = $definedArray[$i];

            if ($routePart != '' && $routePart[0] == ':') //如果url的某个字段以:开头，获取这个字段在queryString中对应的值
            {
                $paramName = substr($routePart, 1);
                
                App::assert(!array_key_exists($paramName, $routeParams));
                $routeParams[$paramName] = $queryArray[$j];
                $i++;
            }
            else if ($routePart == '*') //如果url的某个字段以:开头，获取这个字段在queryString中对应的值
            {
                if (isset($cachedName))
                {
                    App::assert(!array_key_exists($cachedName, $routeParams));
                    $routeParams[$cachedName] = $queryArray[$j];
                    $cachedName = null;
                }
                else
                {
                    $cachedName = $queryArray[$j];
                }
            }
            else
            {
                App::assert($routePart == $queryArray[$j], "Expected: {$routePart}, Actual: {$queryArray[$j]}");
                $i++;
            }
        }

        if (isset($cachedName))
        {
            App::assert(!array_key_exists($cachedName, $routeParams));
            $routeParams[$cachedName] = null;
        }

        $this->_request->setRouteParams($routeParams);
    }

    private function _ruleDotNameMatchCallback($matches)
    {
        return _CONTEXT($matches[1], null, true);
    }

    private function _processRouteRule()
    {
        //获取路由分发所需信息

        $this->_namespace = $this->getRuleProperty('namespace');
        $this->_controllerName = $this->getRuleProperty('controller');

        if (!isset($this->_controllerName))
        {
            if (isset($this->_namespace) && $this->_namespace != Convention::BLUEFIN_NAMESPACE)
            {
                throw new \Bluefin\Exception\ConfigException("Missing \"controller\" setting while \"namespace\" exist. Route: {$this->_routeName}");
            }

            $this->_namespace = Convention::BLUEFIN_NAMESPACE;
            $this->_controllerName = Convention::BLUEFIN_VIEW_CONTROLLER;
            $this->_bypassAction = true;
        }
        else
        {
            list($this->_moduleName, $this->_controllerName, $this->_actionName) = $this->splitDispatchTarget($this->_controllerName);

            if (!isset($this->_actionName))
            {
                throw new \Bluefin\Exception\ConfigException("Action is required. Route: {$this->_routeName}");
            }
        }

        //处理有filters
        if (array_key_exists(Convention::KEYWORD_ROUTE_FILTERS, $this->_routeRule))
        {
            return $this->_processFilters($this->_routeRule[Convention::KEYWORD_ROUTE_FILTERS]);
        }

        return true;
    }

    /**
     * 检查参数是否符合需求。
     * @param $filters array
     * @return bool
     * @throws Exception\ConfigException
     */
    private function _processFilters(array $filters)
    {
        foreach ($filters as $filterName => $filterOptions)
        {
            $filterClassName = usw_to_pascal($filterName) . 'Filter';
            $filterClass = "\\Bluefin\\Filter\\{$filterClassName}";

            try
            {
                $filter = new $filterClass($this);
            }
            catch (\Exception $e)
            {
                throw new \Bluefin\Exception\ConfigException("Invalid filter name: {$filterName}");
            }

            if (false === $filter->filter($filterOptions))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * 根据请求分发到指定的controller:action
     * 成功返回true，失败( 仅当视图模板不存在时 )返回false
     *
     * @throws Exception\PageNotFoundException
     * @return bool
     */
    private function _dispatchRequest()
    {
        $traces = array();
        $parts = array();

        if (!empty($this->_namespace))
        {
            $traces[] = $this->_namespace;
            $parts[] = $this->_namespace;
        }

        $parts[] = 'Controller';

        if (!empty($this->_moduleName))
        {
            $traces[] = $this->_moduleName;
            $parts[] = $this->_moduleName;
        };

        $traces[] = $this->_controllerName;
        $parts[] = $this->_controllerName . 'Controller';

        $traces[] = $this->_actionName;

        $dispatchTrace = implode(' > ', $traces);
        $controllerClass = "\\" . implode("\\", $parts);
        $controllerPath = normalize_dir_separator(implode(DIRECTORY_SEPARATOR, $parts)) . '.php';

        if (0 == $this->_dispatchedTimes)
        {
            //[+]DEBUG
            App::getInstance()->log()->debug("Dispatching route: {$dispatchTrace}");
            //[-]DEBUG
        }
        else
        {
            //[+]DEBUG
            App::getInstance()->log()->debug("Forwarding[#{$this->_dispatchedTimes}] route: {$dispatchTrace}");
            //[-]DEBUG
        }

        $this->_dispatchedTimes++;

        if (false === stream_resolve_include_path($controllerPath))
        {            
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        if ($controllerClass != $this->_controllerClassName)
        {
            $this->_controllerClassName = $controllerClass;
            $this->_controller = new $controllerClass($this, $this->_request, $this->_response);
        }

        if (!$this->_bypassAction)
        {
            $actionName = $this->_actionName . 'Action';
            $this->_controller->preDispatch();

            try
            {
                $this->_controller->$actionName();
            }
            catch (ForwardException $fw)
            {
                $this->_namespace = $fw->namespace;
                $this->_moduleName = $fw->moduleName;
                $this->_controllerName = $fw->controllerName;
                $this->_actionName = $fw->actionName;

                if ($this->_dispatchedTimes >= self::MAX_FORWARD_LIMIT)
                {
                    throw new \Bluefin\Exception\InvalidOperationException("Exceed max forward limit!");
                }

                return false;
            }

            $this->_controller->postDispatch();
        }

        $this->_controller->render();

        return true;
    }
}
