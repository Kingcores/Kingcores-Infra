<?php

use Bluefin\Service;
use SO\Business\SocialBusiness;

class WeiboService extends Service
{
    public function unbind($snaID)
    {
        SocialBusiness::unbind($snaID);
    }
}
