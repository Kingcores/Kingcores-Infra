<?php

namespace Bluefin;

use Bluefin\Convention;

abstract class Controller
{
    protected $_controllerClassName;

    protected $_gateway;
    protected $_request;
    protected $_response;

    protected $_logger;

    /**
     * @var \Bluefin\View
     */
    protected $_view;

    public function __construct(Gateway $gateway, Request $request, Response $response)
    {
        $this->_controllerClassName = get_class($this);

        $this->_gateway = $gateway;
        $this->_request = $request;
        $this->_response = $response;
        $this->_logger = App::getInstance()->log();

        $this->_view = new View();

        $this->_init();
    }

    public function __call($methodName, $args)
    {
        if ('Action' == substr($methodName, -6))
        {
            throw new \Bluefin\Exception\PageNotFoundException($this->_request->getRequestUri());
        }

        App::assert(false, "Method \"{$methodName}\" of class \"{$this->_controllerClassName}\" does not exist.");
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
        $this->_view->setOption('template', $this->getQualifiedViewName($viewTemplate));
        $this->_view->setOption('templateExt', '.html');
    }

    public function preDispatch()
    {
        $this->changeView($this->_gateway->getActionName());
        $this->_view->timestamp = strtotime("now");
        $this->_view->currentLocation = array(
            'module' => $this->_gateway->getModuleName(),
            'controller' => $this->_gateway->getControllerName(),
            'action' => $this->_gateway->getActionName());
    }

    public function postDispatch()
    {
    }

    public function render()
    {
        $this->_response->setBody($this->_view->render());
    }

    /**
     * @param $authName
     * @return Auth\AuthInterface
     */
    public function requireAuth($authName)
    {
        /**
         * @var \Bluefin\Auth\AuthInterface $auth
         */
        $auth = App::getInstance()->auth($authName);
        if (!$auth->isAuthenticated())
        {
            $authUrl = $auth->getAuthUrl();
            $authUrl = strtr($authUrl, array('%redirect_url%' => $this->_gateway->url()));
            $this->_gateway->redirect($authUrl, Common::HTTP_SEE_OTHER);
        }

        return $auth;
    }

    public function getQualifiedViewName($viewName)
    {
        if (false === strpos($viewName, '/'))
        {
            if (false === strpos($viewName, '.'))
            {
                $viewName = $this->_gateway->getControllerName() . '.' . $viewName;
            }

             $viewName = ($this->_gateway->getNamespace() ? strtr($this->_gateway->getNamespace(), '\\', '/') . '/' : '') .
                         ($this->_gateway->getModuleName() ? strtr($this->_gateway->getModuleName(), '\\', '/') . '/' : '') .
                         $viewName;
        }

        return $viewName;
    }

    protected function _init()
    {
    }
}