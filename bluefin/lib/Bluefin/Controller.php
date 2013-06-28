<?php

namespace Bluefin;

use Bluefin\Convention;
use Bluefin\HTML\SimpleComponent;

abstract class Controller
{
    protected $_app;

    protected $_gateway;
    protected $_request;
    protected $_response;

    protected $_logger;
    protected $_requestSource;

    /**
     * @var \Bluefin\View
     */
    protected $_view;

    public function __construct()
    {
        $this->_app = App::getInstance();
        $this->_gateway = $this->_app->gateway();
        $this->_request = $this->_app->request();
        $this->_response = $this->_app->response();
        $this->_logger = $this->_app->log();

        $route =  $this->_gateway->getRouteRule();
        $this->_view = new View(array_try_get($route, 'view', []));
        $this->_view->set('_', new \Bluefin\Util\TwigVarHelper());

        $this->_init();
    }

    public function __call($methodName, $args)
    {
        if ('Action' == substr($methodName, -6))
        {
            throw new \Bluefin\Exception\PageNotFoundException($this->_request->getRequestUri());
        }

        throw new \Bluefin\Exception\ServerErrorException(null, Common::HTTP_NOT_IMPLEMENTED);
    }

    public function getGateway()
    {
        return $this->_gateway;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->_view;
    }

    public function changeView($viewTemplate)
    {
        if (mb_substr($viewTemplate, -5) == '.html')
        {
            $this->_view->setOption('template', $viewTemplate);
        }
        else
        {
            $this->_view->setOption('template', $this->getQualifiedViewName($viewTemplate) . '.html');
        }
    }

    public function preDispatch()
    {
        $this->changeView($this->_gateway->getActionName());

        $this->_requestSource = $this->_request->get(Convention::KEYWORD_REQUEST_FROM);

        if (isset($this->_requestSource) && substr($this->_requestSource, 0, 3) == 'B64')
        {
            $original = base64_decode(substr($this->_requestSource, 3));
            if (false !== $original)
            {
                $this->_app->log()->debug("Source url: " . $original, \Bluefin\Log::CHANNEL_DIAG);
                $this->_requestSource = $original;
            }
        }
    }

    public function postDispatch()
    {
        $this->_view->set('_componentScripts', SimpleComponent::$scripts);

        if (isset($this->_requestSource))
        {
            $this->_view->set('_from', $this->_requestSource);
        }
    }

    public function render()
    {
        $this->_response->setBody($this->_view->render());
    }

    public function getQualifiedViewName($viewName)
    {
        if (false === strpos($viewName, DIRECTORY_SEPARATOR))
        {
            if (false === strpos($viewName, '.'))
            {
                $viewName = $this->_gateway->getControllerName() . '.' . $viewName;
            }

            $viewName = $this->_gateway->getModulePath() . $viewName;

            if (!is_null($this->_gateway->getNamespace()))
            {
                $viewName = $this->_gateway->getNamespace() . DIRECTORY_SEPARATOR . $viewName;
            }

            $viewName = normalize_dir_separator($viewName);
        }

        return $viewName;
    }

    protected function _init()
    {
    }

    protected function _backToRequestSource(array $queryParams = null)
    {
        if (isset($queryParams))
        {
            $url = build_uri($this->_requestSource, $queryParams);
        }
        else
        {
            $url = $this->_requestSource;
        }

        $this->_gateway->redirect($url);
    }

    protected function _redirectWithSource($url, array $queryParams = null, $useThisIfNoSource = false)
    {
        $requestSource = $this->_requestSource;

        if (!isset($requestSource) && $useThisIfNoSource)
        {
            $requestSource = $this->_request->getFullRequestUri();
        }

        if (isset($requestSource))
        {
            $queryParams[Convention::KEYWORD_REQUEST_FROM] = b64_encode($requestSource);
        }

        if (is_abs_url($url))
        {
            $url = build_uri($url, $queryParams, null);
        }
        else
        {
            $url = $this->_gateway->url($url, $queryParams, null);
        }

        $this->_gateway->redirect($url);
    }

    /**
     * @param $authName
     * @return Auth\AuthInterface
     */
    protected function _requireAuth($authName)
    {
        /**
         * @var \Bluefin\Auth\AuthInterface $auth
         */
        $auth = $this->_app->auth($authName);
        if (!$auth->isAuthenticated())
        {
            $authUrl = $auth->getAuthUrl(isset($this->_requestSource) ? $this->_requestSource : $this->_gateway->path());
            $this->_gateway->redirect($authUrl, Common::HTTP_SEE_OTHER);
        }

        return $auth;
    }

    /**
     * 将提交的POST数据传递回视图。
     */
    protected function _transferPostStates()
    {
        $this->_view->appendData($this->_request->getPostParams());
        throw new \Bluefin\Exception\SkipException();
    }

    protected function _setViewFromQuery($queryParamName, $viewParamName = null, $default = null)
    {
        isset($viewParamName) || ($viewParamName = $queryParamName);
        $this->_view->set($viewParamName, $this->_request->getQueryParam($queryParamName, $default));
    }

    /**
     * 按照Form组件的字段过滤提交的数据。
     */
    protected function _filterInput(array $fields, array $input)
    {
        $result = [];

        foreach ($fields as $fieldName => $stuff)
        {
            if (is_int($fieldName))
            {
                $fieldName = $stuff;
            }

            if (!array_key_exists($fieldName, $input))
            {
                throw new \Bluefin\Exception\InvalidRequestException();
            }

            $result[$fieldName] = $input[$fieldName];
        }

        return $result;
    }

    protected function _sendJsonAndExit($json)
    {
        ob_end_clean();
        header("HTTP/1.1 200", true);
        header("Content-Type: application/json;charset=utf-8", true);
        header("Cache-Control: no-store", true);

        if (!is_string($json))
        {
            $json = json_encode($json);
        }

        echo $json;
        exit();
    }
}