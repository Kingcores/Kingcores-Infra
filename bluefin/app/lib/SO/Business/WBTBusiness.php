<?php

namespace SO\Business;

class WBTBusiness
{
    const WEIBOTUI_WEIBO_ID = '1882037885';

    private static $__wbtClient;

    public static function getClient()
    {
        if (!isset(self::$__wbtClient))
        {
            require_once 'WBTSdk/WBTPrivateClient.php';
            self::$__wbtClient = new \WBTPrivateClient('10000', '46f1ee3fb5e6833b645cb55bedeeb053');
        }

        return self::$__wbtClient;
    }
}
