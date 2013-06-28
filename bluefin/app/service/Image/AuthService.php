<?php

use Bluefin\Service;

class AuthService extends Service
{
    // weibotui.com/bin/image/auth/image
    public function image()
    {
        $this->_controller->getResponse()->setHeader('Cache-Control: no-cache, must-revalidate');
        $this->_controller->getResponse()->setHeader('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        $autocode = new SimpleCaptcha();
        $autocode->CreateImage();
    }
}
