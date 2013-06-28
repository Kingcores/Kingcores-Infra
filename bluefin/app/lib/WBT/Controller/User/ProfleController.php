<?php

namespace WBT\Controller\User;

use Bluefin\App;
use Bluefin\Controller;

class ProfleController extends Controller
{
    public function indexAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 用户资料";
    }
}