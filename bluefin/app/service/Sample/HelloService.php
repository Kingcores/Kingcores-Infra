<?php

use Bluefin\App;
use Bluefin\Service;

class HelloService extends Service
{
    public function world()
    {
        return ['hello' => 'world', 'sample' => 'ok'];
    }

    public function hi()
    {
        return $this->_controller->getRequest()->get('echo');
    }

    public function protect()
    {
        $this->_blockExternalCall();

        return 'ok';
    }

    public function testPay()
    {
        return ['errno' => 1];
    }
}
