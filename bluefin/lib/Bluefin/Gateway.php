<?php

namespace Bluefin;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Exception\ForwardException;
use Bluefin\Exception\RedirectException;
use Bluefin\Exception\RequestException;
use Bluefin\Exception\SkipException;
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
class Gateway implements ContextProviderInterface
{
    const MAX_FORWARD_LIMIT = 3;

    public static function splitDispatchTarget($name)
    {
        $parts = explode('.', $name);
        $tail = array_pop($parts);
        $body = array_pop($parts);
        $head = empty($parts) ? null : implode('.', $parts);

        return array($head, $body, $tail);
    }

    /**
     * @var App
     */
    protected $_app;

    /**
     * HTTP请求
     * @var Request
     */
    private $_request;

    private $_response;

    private $_namespace;

    private $_moduleName;

    private $_controllerToken;

    private $_controllerName;

    private $_actionToken;

    private $_actionName;

    private $_modulePath;

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

    private $_host;

    private $_routingTable;

    private $_requestRoute;

    private $_serverUrlRewritable;

    private $_dispatchedTimes;

    private $_locationTrace;

    public function __construct()
    {
        $this->_app = App::getInstance();
        $this->_request = $this->_app->request();
        $this->_response = $this->_app->response();
        $this->_cache = [];
        $this->_routingTable = $this->_app->config('routing');
        $this->_serverUrlRewritable = _C('config.app.serverUrlRewritable', false);
        $this->_dispatchedTimes = 0;
        $this->_host = $this->_request->getHost();
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
     * 返回模块路径
     * @return mixed
     */
    public function getModulePath()
    {
        return $this->_modulePath;
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

    /**
     * @return array
     */
    public function getLocationStack()
    {
        return $this->_locationTrace;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function getContext($name)
    {
        switch ($name)
        {
            case 'namespace':
                return $this->_namespace;

            case 'module':
                return $this->_moduleName;

            case 'controller':
                return $this->_controllerToken;

            case 'action':
                return $this->_actionToken;

            case 'route':
                return $this->getRouteName();

            case 'host':
                return $this->_host;

            case 'url':
                return $this->url();

            case 'path':
                return $this->path();

            case 'client_ip':
                return $this->_request->getClientIP();

            case 'referer':
                return $this->_request->getReferer();

            default:
                throw new ServerErrorException("Unknown gateway parameter: {$name}!");
        }
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
        return VarText::parseVarText($value);
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
            $this->_response->setRedirect($redirEx->targetUrl, $redirEx->statusCode);

            //[+]DEBUG
            $this->_app->log()->debug("Redirected to \"{$redirEx->targetUrl}\".");
            //[-]DEBUG
        }
        catch (RequestException $reqEx)
        {
            //转到请求错误页
            $this->_response->setException($reqEx);
            $errorCode = $reqEx->getCode();
            $message = $reqEx->getMessage();

            $this->_app->log()->info('Request Exception: ' . $reqEx->getMessage());
        }
        catch (ServerErrorException $srvEx)
        {
            //转到服务器错误页
            $this->_response->setException($srvEx);
            $errorCode = $srvEx->getCode();
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);

            $this->_app->log()->alert(
                'Server Error: ' . $srvEx->getMessage() . "\n" . $srvEx->getTraceAsString()
            );
        }
        catch (Exception $e)
        {
            $this->_response->setException($e);
            $errorCode = \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE;
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);

            $this->_app->log()->error('Unknown Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
                $gwConfig = $this->_app->config('gateway');
                if (isset($gwConfig))
                {
                    $template = array_try_get($gwConfig, 'exceptionView');

                    if (!empty($template))
                    {
                        $view = new View();
                        $view->setOption('template', $template);
                        $view->set('title', _APP_('ERROR'));
                        $view->set('code', $errorCode);
                        $view->set('message', $message);

                        $message = $view->render();
                    }
                }

                $this->_response->setBody($message);
            }
        }

        $this->_response->sendResponse();

        $passedTime = $this->_app->elapsedTime();
        $this->_app->log()->info('Execution time: ' . $passedTime . '(s).');
    }

    public function forward($actionName, $controllerName = null, $moduleName = null, $namespace = null)
    {
        isset($namespace) || ($namespace = $this->_namespace);

        if (!isset($moduleName))
        {
            $moduleName = $this->_moduleName;
        }

        if (!isset($controllerName))
        {
            $controllerName = $this->_controllerToken;
        }

        throw new \Bluefin\Exception\ForwardException($namespace, $moduleName, $controllerName, $actionName);
    }

    public function redirect($uri, $code = Common::HTTP_FOUND)
    {
        throw new \Bluefin\Exception\RedirectException($uri, $code);
    }

    public function url($relativeUrl = null, array $queryParams = null, array $fragmentParams = null, $https = false)
    {
        if (!isset($relativeUrl))
        {
            $url = $this->_request->getFullRequestUri();
        }
        else if (!is_abs_url($relativeUrl))
        {
            $url = $this->_app->rootUrl() . $relativeUrl;
        }
        else
        {
            $url = $relativeUrl;
        }

        if ($https)
        {
            $url = preg_replace('/^http:\/\//', 'https://', $url);
        }
        else
        {
            $url = preg_replace('/^https:\/\//', 'http://', $url);
        }

        return build_uri($url, $queryParams, $fragmentParams);
    }

    public function path($relativeUrl = null, array $queryParams = null, array $fragmentParams = null)
    {
        if (!isset($relativeUrl))
        {
            $url = $this->_request->getRequestUri();
        }
        else
        {
            $url = $this->_app->basePath() . $relativeUrl;
        }

        return build_uri($url, $queryParams, $fragmentParams);
    }

    public function route($routeName = null, $params = null, array $queryParams = null, array $fragmentParams = null)
    {
        if (isset($routeName))
        {//设置了路由名称
            //确保路由名称合法
            if (!array_key_exists($routeName, $this->_routingTable))
            {
                throw new \Bluefin\Exception\InvalidOperationException("Unknown route name: {$routeName}");
            }

            $routeRule = $this->_routingTable[$routeName];
        }
        else
        {
            if (!isset($params))
            {//没有提供路由规则需要的参数，使用当前请求
                $uri = $this->_request->getScriptRelativePath() . str_pad_if($this->_requestRoute, '/', true, false);
                return build_uri($uri, $queryParams, $fragmentParams);
            }

            //使用当前路由规则
            $routeRule = $this->_routeRule;
        }

        $route = $routeRule['route'];

        if (mb_strpos($route, '/:') === false)
        {//无路由参数要求
            $uri = $this->_request->getScriptRelativePath() . $route;
        }
        else
        {//使用当前路由的参数来填充不足的参数
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
                $pos = mb_strpos($token, '/');
                if ($pos !== false) $token = mb_substr($token, 0, $pos);

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
                $uri = $this->_request->getScriptRelativePath() . str_pad_if($route, '/', true, false);
            }
            else
            {
                $uri = $this->_request->getScriptRelativePath();
                isset($queryParams) || ($queryParams = array());

                $queryParams[Convention::KEYWORD_REQUEST_ROUTE] = $route;
            }
        }

        return build_uri($uri, $queryParams, $fragmentParams);
    }

    private function _runService()
    {
        $requestRoute = $this->_request->getQueryParam(Convention::KEYWORD_REQUEST_ROUTE, '', true);
        unset($_GET[Convention::KEYWORD_REQUEST_ROUTE]);
        $this->_requestRoute = isset($requestRoute) ? trim($requestRoute, '/') : '';

        if (false !== mb_strpos($this->_requestRoute, ':') || !$this->_findRoute())
        {
            throw new \Bluefin\Exception\PageNotFoundException($this->_request->getRequestUri());
        }

        //[+]DEBUG
        $this->_app->log()->debug("Found route: {$this->_routeName}");
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
     * @throws \Bluefin\Exception\ConfigException
     */
    private function _findRoute()
    {
        $replace = array('/*' => '(|\/[\S]+)', '/' => '\/');

        foreach ($this->_routingTable as $key => $val)
        {
            if (!isset($val['route']))
            {
                throw new \Bluefin\Exception\ConfigException(
                    "The route property of the routing item \"{$key}\" does not exist. Route is ignored."
                );
            }

            if (isset($val['host']) && $val['host'] != $this->_host)
            {
                continue;
            }

            $definedURL = trim($val['route'], '/');

            if ($definedURL == $this->_requestRoute)
            {
                $this->_setRoute($key, $val, $this->_requestRoute, $definedURL);
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

        $iMax = count($definedArray);
        $jMax = count($queryArray);

        for ($i = 0; $i < $iMax; $i++)
        {
            $routePart = $definedArray[$i];

            if ($routePart != '' && $routePart[0] == ':')
            {//如果url的某个字段以:开头，获取这个字段在queryString中对应的值
                $paramName = mb_substr($routePart, 1);
                
                App::assert(!array_key_exists($paramName, $routeParams));
                $routeParams[$paramName] = $queryArray[$i];
            }
            else if ($routePart == '*') //如果url的某个字段以为*，则后续字段依次存入数组
            {
                if (isset($queryArray[$i]))
                {
                    $routeParams[0] = $queryArray[$i];
                }

                if ($i != $iMax - 1)
                {
                    throw new \Bluefin\Exception\ConfigException(
                        "'*' can only be used in the last part of a route rule."
                    );
                }
            }
            else
            {
                App::assert($routePart == $queryArray[$i], "Expected: {$routePart}, Actual: {$queryArray[$i]}");
            }
        }

        for ($i = $iMax; $i < $jMax; $i++)
        {
            $routeParams[$i - $iMax + 1] = $queryArray[$i];
        }

        $this->_request->setRouteParams($routeParams);
    }

    private function _processRouteRule()
    {
        //获取路由分发所需信息
        $this->_namespace = $this->getRuleProperty('namespace');
        $actionFullPath = $this->getRuleProperty('action');

        if (!isset($actionFullPath))
        {
            if (isset($this->_namespace) && $this->_namespace != Convention::BLUEFIN_NAMESPACE)
            {
                throw new \Bluefin\Exception\ConfigException("Missing \"action\" setting while \"namespace\" exist. Route: {$this->_routeName}");
            }

            $this->_namespace = Convention::BLUEFIN_NAMESPACE;
            $this->_controllerToken = Convention::BLUEFIN_VIEW_CONTROLLER;
            $this->_actionToken = null;
            $this->_bypassAction = true;
        }
        else
        {
            $this->_bypassAction = false;
            list($this->_moduleName, $this->_controllerToken, $this->_actionToken) = self::splitDispatchTarget($actionFullPath);

            if (!isset($this->_actionToken) || !isset($this->_controllerToken))
            {
                throw new \Bluefin\Exception\ConfigException("Controller and action are required. Route: {$this->_routeName}");
            }
        }

        //处理有filters
        /*
        if (array_key_exists(Convention::KEYWORD_ROUTE_FILTERS, $this->_routeRule))
        {
            return $this->_processFilters($this->_routeRule[Convention::KEYWORD_ROUTE_FILTERS]);
        }
        */

        return true;
    }

    /**
     * 根据请求分发到指定的controller:action
     * 成功返回true，失败( 仅当视图模板不存在时 )返回false
     *
     * @throws \Bluefin\Exception\PageNotFoundException
     * @throws \Bluefin\Exception\InvalidOperationException
     * @return bool
     */
    private function _dispatchRequest()
    {
        $this->_locationTrace = [];
        $parts = [];

        if (!empty($this->_namespace))
        {
            $this->_locationTrace[] = $this->_namespace;
            $parts[] = $this->_namespace;
        }

        $parts[] = 'Controller';

        if (!empty($this->_moduleName))
        {
            $modules = explode('.', $this->_moduleName);
            $this->_modulePath = '';
            foreach ($modules as &$module)
            {
                $module = usw_to_pascal($module);
                $this->_modulePath .= $module . DIRECTORY_SEPARATOR;
                $parts[] = $module;
            }

            $this->_locationTrace[] = implode('.', $modules);
        };

        $this->_controllerName = usw_to_pascal($this->_controllerToken);
        $this->_actionName = usw_to_camel($this->_actionToken);

        $this->_locationTrace[] = $this->_controllerName;
        $this->_locationTrace[] = $this->_actionName;
        $parts[] = $this->_controllerName . "Controller";

        $dispatchTrace = implode(' > ', $this->_locationTrace);
        $controllerClass = "\\" . implode("\\", $parts);
        $controllerPath = normalize_dir_separator(implode(DIRECTORY_SEPARATOR, $parts)) . '.php';

        if (0 === $this->_dispatchedTimes)
        {
            $this->_app->log()->verbose("Dispatching route: {$dispatchTrace}");
        }
        else
        {
            $this->_app->log()->verbose("Forwarding[#{$this->_dispatchedTimes}] route: {$dispatchTrace}");
        }

        $this->_dispatchedTimes++;

        if (false === stream_resolve_include_path($controllerPath))
        {            
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        if ($controllerClass != $this->_controllerClassName)
        {
            $this->_controllerClassName = $controllerClass;
            $this->_controller = new $controllerClass();
        }

        if (!$this->_bypassAction)
        {
            $actionName = $this->_actionName . 'Action';

            ob_start();

            try
            {
                $this->_controller->preDispatch();
                $this->_controller->$actionName();
            }
            catch (SkipException $se)
            {
            }
            catch (ForwardException $fw)
            {
                ob_end_clean();

                $this->_namespace = $fw->namespace;
                $this->_moduleName = $fw->moduleName;
                $this->_controllerToken = $fw->controllerName;
                $this->_actionToken = $fw->actionName;

                if ($this->_dispatchedTimes >= self::MAX_FORWARD_LIMIT)
                {
                    throw new \Bluefin\Exception\InvalidOperationException("Exceed max forward limit!");
                }

                return false;
            }

            $this->_controller->postDispatch();

            $obLength = ob_get_length();
            if (false === $obLength || 0 === $obLength)
            {
                ob_end_clean();
                $this->_controller->render();
            }
            else
            {
                $this->_response->setBody(ob_get_clean());
            }
        }
        else
        {
            $this->_controller->render();
        }

        return true;
    }
}
