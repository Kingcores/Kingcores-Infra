<?php

namespace WBT\Controller\Agent;

use Bluefin\App;
use Bluefin\Controller;

class TuiController extends Controller
{
    public function indexAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 代理";
    }
}