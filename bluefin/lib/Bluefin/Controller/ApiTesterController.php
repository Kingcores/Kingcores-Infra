<?php

namespace Bluefin\Controller;

use Bluefin\Controller;

class ApiTesterController extends Controller
{
    protected function _init()
    {
        parent::_init();

        $this->_view->setOption('root', BLUEFIN_BUILTIN . '/view');
    }

    public function indexAction()
    {

    }
}
