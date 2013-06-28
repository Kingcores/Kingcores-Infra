<?php

namespace SO\Business;

use WBT\Model\Weibotui\WeiboLoginRecord;
use WBT\Model\Weibotui\WeiboType;
use WBT\Model\Weibotui\WeiboToken;
use WBT\Model\Weibotui\Weibo;

class SocialBusiness
{
    public static function recordSnaLogin($snaID)
    {
        //保存登录记录
        $weiboLoginRecord = new WeiboLoginRecord();
        $weiboLoginRecord->setWeibo($snaID);
        $weiboLoginRecord->insert();
    }

    public static function getProfilesOfUserSocialAccounts($userId)
    {
        $appKey = _C("config.weibo.weibotui.appKey");

        return WeiboToken::fetchRows(['access_token', 'expires_at', 'weibo.*' => ''], ['weibo.user' => $userId, 'app_key' => $appKey], null, [Weibo::_CREATED_AT]);
    }

    public static function getSocialAccountProfile($snaID)
    {
        $appKey = _C("config.weibo.weibotui.appKey");

        return WeiboToken::fetchOneRow(['access_token', 'expires_at', 'weibo.*' => ''], ['weibo' => $snaID, 'app_key' => $appKey]);
    }

    public static function bind($snaID, $userID)
    {
        $weibo = new Weibo($snaID);
        _NON_EMPTY($weibo);

        $weibo
            ->setUser($userID)
            ->update();
    }

    public static function unbind($snaID)
    {
        $weibo = new Weibo($snaID);
        _NON_EMPTY($weibo);

        _DEBUG($weibo->data());

        $weibo
            ->setUser(null)
            ->update();
    }
}
