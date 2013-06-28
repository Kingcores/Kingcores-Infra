<?php

namespace QQWeibo;

class QQWeiboClientFactory
{
    private static $__clientCache;

    public static function createFromConfig($appName, $accessToken = null, $uid = null)
    {
        $key = "config.qqweibo.{$appName}";

        isset(self::$__clientCache) || (self::$__clientCache = []);

        if (array_key_exists($key, self::$__clientCache))
        {
            return self::$__clientCache[$key];
        }

        $weiboConfig = _C($key);

        if (empty($weiboConfig) || !all_keys_exists(['appKey', 'appSecret'], $weiboConfig))
        {
            throw new \Bluefin\Exception\ConfigException("Invalid qqweibo client configuration!");
        }

        $appKey = $weiboConfig['appKey'];
        $appSecret = $weiboConfig['appSecret'];


        $c = new QQWeiboClient($appKey, $appSecret, $accessToken, $uid);

        self::$__clientCache[$key] = $c;

        return $c;
    }

    public static function createFromKeySecret($appKey, $appSecret, $accessToken, $uid)
    {
        return new QQWeiboClient($appKey, $appSecret, $accessToken, $uid);
    }
}
