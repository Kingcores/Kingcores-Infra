<?php

namespace WBT\Controller\Weibo;

use Bluefin\App;
use Bluefin\Controller;

class SpaceController extends Controller
{
    public function indexAction()
    {
        //TODO: write your action code here

        $this->_view->title = "微博推 - 我的微视界";
    }
}