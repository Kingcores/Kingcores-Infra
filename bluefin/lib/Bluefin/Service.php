<?php

namespace Bluefin;

/**
 * class Service
*/
abstract class Service
{
    protected $_app;

    public function __construct()
    {
        $this->_app = App::getInstance();
    }

    public function __call($methodName, $args)
    {
        throw new \Bluefin\Exception\RequestException(null, Common::HTTP_NOT_IMPLEMENTED);
    }

    protected function _blockExternalCall()
    {
        if ($this->_app->session()->get('counter', 0) == 0)
        {
            $_SESSION = [];
            throw new \Bluefin\Exception\UnauthorizedException();
        }
    }

    protected function _requireAuth($authName)
    {
        /**
         * @var \Bluefin\Auth\AuthInterface $auth
         */
        $auth = $this->_app->auth($authName);
        if (!$auth->isAuthenticated())
        {
            throw new \Bluefin\Exception\UnauthorizedException();
        }

        return $auth;
    }

    protected function _referrerCheck()
    {

    }
}