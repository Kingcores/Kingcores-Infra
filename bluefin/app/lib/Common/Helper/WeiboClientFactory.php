<?php

namespace Common\Helper;

class WeiboClientFactory
{
    private static $__clientCache;

    public static function createFromConfig($appName, $accessToken = null)
    {
        $key = "config.weibo.{$appName}";

        isset(self::$__clientCache) || (self::$__clientCache = []);

        if (array_key_exists($key, self::$__clientCache))
        {
            return self::$__clientCache[$key];
        }

        $weiboConfig = _C($key);

        if (empty($weiboConfig) || !all_keys_exists(['appKey', 'appSecret'], $weiboConfig))
        {
            throw new \Bluefin\Exception\ConfigException("Invalid weibo client configuration!");
        }

        $appKey = $weiboConfig['appKey'];
        $appSecret = $weiboConfig['appSecret'];

        require_once('Weibo/saetv2.ex.class.php');

        $c = new \SaeTClientV2($appKey, $appSecret, $accessToken);

        self::$__clientCache[$key] = $c;

        return $c;
    }

    public static function createFromKeySecret($appKey, $appSecret, $accessToken)
    {
        require_once('Weibo/saetv2.ex.class.php');

        return new \SaeTClientV2($appKey, $appSecret, $accessToken);
    }
}
