<?php

namespace WBTAdmin\Controller;

use WBTAdmin\Business\UserBusiness;
use Bluefin\Auth\AuthHelper;

class UserController extends WBTAdminControllerBase
{
    public function indexAction()
    {
        $wbtAdmin = $this->_requireAuth('wbt_admin');


    }

    public function onlineListAction()
    {
        $wbtAdmin = $this->_requireAuth('wbt_admin');

        $list = UserBusiness::getUserSessions();

        //_ARR_DUMP($list);
    }
}
