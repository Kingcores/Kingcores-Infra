<?php

namespace WBTAdmin\Controller;

use WBTAdmin\Business\AuthBusiness;
use Bluefin\Auth\AuthHelper;
use Common\Data\Event;

class HomeController extends WBTAdminControllerBase
{
    public function indexAction()
    {
        $wbtAdmin = $this->_requireAuth('wbt_admin');

    }

    public function loginAction()
    {
        if ($this->_request->isPost())
        {
            $flag = AuthBusiness::login($this->_request->getPostParams());

            if (AuthHelper::SUCCESS === $flag)
            {
                if (isset($this->_requestSource))
                {
                    $this->_backToRequestSource();
                }

                $this->_gateway->redirect($this->_gateway->path('home/index'));
            }

            $this->_setEventMessage($flag, Event::SRC_AUTH);
        }
    }

    public function logoutAction()
    {
        AuthBusiness::logout();
        $this->_gateway->redirect($this->_gateway->path('home/login'));
    }

    public function sessionAction()
    {
        if (ENV != 'dev')
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        _ARR_DUMP($_SESSION);
    }

    public function redisAction()
    {
        if (ENV != 'dev')
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        _ARR_DUMP($this->_app->cache('l1')->info());
    }
}
