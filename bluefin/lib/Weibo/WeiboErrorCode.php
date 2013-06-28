<?php

namespace Weibo;

class WeiboErrorCode
{
    private static $__errorCodeMessages;

    public static function getErrorMessage($code)
    {
        return "微博接口调用失败，错误信息完善中...";

        if (!isset(self::$__errorCodeMessages))
        {
            self::$__errorCodeMessages = Array(

            );
        }

        return self::$__errorCodeMessages[$status];
    }
}
