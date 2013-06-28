<?php

namespace SO\Business;

use Bluefin\App;
use WBT\Model\Weibotui\User;
use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\WeiboToken;
use WBT\Model\Weibotui\LoginType;
use WBT\Model\Weibotui\WeiboLoginRecord;

use QQWeibo\QQWeiboClientFactory;

class QQWeiboBusiness
{
    public static function recordWeiboLogin($uid)
    {
        //保存登录记录
        $weiboLoginRecord = new QQWeiboLoginRecord();
        $weiboLoginRecord->setUID($uid);
        $weiboLoginRecord->insert();
    }

    public static function getWeiboTokensAndProfilesFromDbByUserID($user_id)
    {
        $appKey = _C("config.qqweibo.weibotui.appKey");

        return QQWeiboToken::fetchRows(['*', 'uid.*'], ['qq_weibo.user' => $user_id, 'app_key' => $appKey], null,
            []);
    }

    public static function getWeiboTokenAndProfileFromDbByWeiboUID($uid)
    {
        $appKey = _C("config.qqweibo.weibotui.appKey");
        log_debug("uid[$uid] appKey[$appKey]");

        return QQWeiboToken::fetchOneRow(['*', 'uid.*'], ['uid' => $uid, 'app_key' => $appKey]);
    }

    public static function getWeiboProfileByToken(array $tokenData)
    {
        //获取用户最新的档案
        $c = QQWeiboClientFactory::createFromKeySecret($tokenData['app_key'], $tokenData['app_secret'],
            $tokenData['access_token'], $tokenData['uid']);

        $profile = $c->user_info();

        if($profile['ret'] !== 0){
            log_warning('tokenData:' . var_export($tokenData,true) . 'user_info error:'.var_export($profile,true));
        }
        return $profile;
    }

    public static function getWeiboHomeTimeline(array $tokenData)
    {
        //log_debug('weibodata:'.var_export($weiboData,true));
        $c = QQWeiboClientFactory::createFromKeySecret($tokenData['app_key'], $tokenData['app_secret'],
            $tokenData['access_token'], $tokenData['uid']);

        $homeTimeline = $c->home_timeline();
        //_DEBUG($homeTimeline,'homeTimeLine');
        if($homeTimeline['ret'] !== 0){
            log_warning('QQWeiboClient->user_info() failed. tokenData:' . var_export($tokenData,true)
                .'.homeTimeline:' . var_export($homeTimeline,true));
        }

        return $homeTimeline;
    }

    public static function checkWeiboServiceResult($result)
    {
        if (empty($result))
        {
            throw new \Bluefin\Exception\VendorServiceException(
                _APP_("Weibo API returned empty result.")
            );
        }

        if (is_array($result) && $result['ret'] !== 0)
        {
            throw new \Bluefin\Exception\VendorServiceException(
                _T($result['ret'], 'qq_weibo')
            );
        }
    }
}
