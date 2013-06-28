<?php

namespace WBT\Controller\Order;

use Bluefin\App;
use Bluefin\Controller;

class PrepaidController extends Controller
{
    public function indexAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 充值";
    }
    public function listAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 充值记录";
    }
}