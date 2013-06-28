<?php

namespace WBTAdmin\Business;

use Bluefin\App;

use WBT\Model\Weibotui\AdminLoginRecord;
use WBT\Model\Weibotui\AdminWithRole;
use WBT\Model\Weibotui\Admin;
use WBT\Model\Weibotui\UserStatus;

class UserBusiness
{
    public static function getUserSessions()
    {
        $cache = App::getInstance()->cache('session');

        $keys = $cache->info('all');
        _ARR_DUMP($keys);
        return;

        $list = $cache->mGet($keys);

        foreach ($list as &$row)
        {
            echo $row;
            //$row = unserialize($row);
        }

        return $list;
    }
}
