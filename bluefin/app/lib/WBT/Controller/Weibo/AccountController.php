<?php

namespace WBT\Controller\Weibo;

use Bluefin\App;
use Bluefin\Controller;

class AccountController extends Controller
{
    public function indexAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 微博账号设置";
    }

    public function changeAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 微博账号切换";
    }
}