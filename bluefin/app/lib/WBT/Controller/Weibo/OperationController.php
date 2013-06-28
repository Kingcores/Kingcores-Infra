<?php

namespace WBT\Controller\Weibo;

use Bluefin\App;
use Bluefin\Controller;

class OperationController extends Controller
{
    public function postAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 发微博";
    }
}