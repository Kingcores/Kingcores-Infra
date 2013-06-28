<?php

namespace SO\Business;

use Bluefin\App;
use Bluefin\Data\DbCondition;
use Common\Helper\WeiboClientFactory;
use WBT\Model\Weibotui\User;
use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\WeiboType;
use WBT\Model\Weibotui\WeiboToken;
use WBT\Model\Weibotui\LoginType;
use WBT\Model\Weibotui\WeiboLoginRecord;
use WBT\Model\Weibotui\SinaDingshiWeibo;
use WBT\Model\Weibotui\DingshiWeiboStatus;
use WBT\Model\Weibotui\Gender;

use SO\Business\SocialBusiness;

class SinaWeiboBusiness
{
    /**
     * [Reviewed]
     * @param $accessToken
     * @param $uid
     * @return array
     */
    public static function getLoggedInWeiboData($accessToken, $uid)
    {
        $weiboProfile = SinaWeiboBusiness::getProfile($accessToken, $uid);

        $weiboData = [
            Weibo::TYPE => WeiboType::WEIBO,
            Weibo::UID => $uid,
            Weibo::URL => 'http://weibo.com/' . (empty($weiboProfile['profile_url']) ? $uid : $weiboProfile['profile_url']),
            Weibo::DISPLAY_NAME => $weiboProfile['screen_name'],
            Weibo::AVATAR_S => $weiboProfile['profile_image_url'],
            Weibo::AVATAR_L => $weiboProfile['avatar_large'],
            Weibo::LOCATION => $weiboProfile['location'],
            Weibo::DESCRIPTION => $weiboProfile['description'],
            Weibo::GENDER => SinaWeiboBusiness::translateGenderType($weiboProfile['gender']),
            Weibo::WBT_HOME => '/w' . $uid,
            Weibo::NUM_FOLLOWER => $weiboProfile['followers_count'],
            Weibo::NUM_FOLLOWING => $weiboProfile['friends_count'],
            Weibo::NUM_POST => $weiboProfile['statuses_count'],
            Weibo::NUM_LIKE => $weiboProfile['favourites_count']
        ];

        return $weiboData;
    }

    /**
     * [Reviewed]
     * @param $accessToken
     * @param $uid
     * @return array
     * @throws \Bluefin\Exception\VendorServiceException
     */
    public static function getProfile($accessToken, $uid)
    {
        $appKey = _C("config.weibo.weibotui.appKey");
        $appSecret = _C("config.weibo.weibotui.appSecret");

        $c = WeiboClientFactory::createFromKeySecret($appKey, $appSecret, $accessToken);
        $profile = null;

        try
        {
            $profile = $c->show_user_by_id($uid);
        }
        catch (\OAuthException $e)
        {
            throw new \Bluefin\Exception\VendorServiceException(_APP_('Failed to get weibo profile!'), $e);
        }

        SinaWeiboBusiness::checkWeiboServiceResult($profile);

        return $profile;
    }

    /**
     * [Reviewed]
     * @param $accessToken
     * @param $uid
     * @return array
     * @throws \Bluefin\Exception\VendorServiceException
     */
    public static function getUserTimeline($accessToken, $uid)
    {
        $appKey = _C("config.weibo.weibotui.appKey");
        $appSecret = _C("config.weibo.weibotui.appSecret");

        $c = WeiboClientFactory::createFromKeySecret($appKey, $appSecret, $accessToken);
        $userTimeline = null;

        try
        {
            $userTimeline = $c->user_timeline_by_id($uid);
        }
        catch (\OAuthException $e)
        {
            throw new \Bluefin\Exception\VendorServiceException(_APP_("Failed to get user's home timeline!"), $e);
        }

        SinaWeiboBusiness::checkWeiboServiceResult($userTimeline);

        return $userTimeline;
    }

    public static function translateGenderType($weiboGender)
    {
        $mapper = [
            'm' => Gender::MALE,
            'f' => Gender::FEMALE
        ];

        return array_try_get($mapper, $weiboGender, Gender::UNKNOWN);
    }

    public static function getWeiboHomeTimeline(array $weiboData)
    {
        $c = WeiboClientFactory::createFromKeySecret($weiboData['app_key'], $weiboData['app_secret'], $weiboData['access_token']);

        $homeTimeline = null;

        try
        {
            $homeTimeline = $c->home_timeline();
        }
        catch (\OAuthException $e)
        {
            App::getInstance()->log()->error("Weibo 'statuses/home_timeline' failed! Code: {$e->getCode()}, Detail: {$e->getMessage()}");
        }

        return $homeTimeline;
    }

    // 检查当前登陆用户是否拥有指定微博
    // 1 ：当前登陆的微博用户就是本身
    // 2 ：微博推的绑定账号有这个 $uid
    public static function isOwnerOf($uid)
    {
        $uid = strval($uid);
        $is_owner = false;
        $loginProfile = App::getInstance()->session()->get('login_profile');
        $loginType = $loginProfile['type'];

        if ($loginType === AuthBusiness::AUTH_WEIBOTUI)
        {
            $weibotuiAuth = App::getInstance()->auth('weibotui');
            if ($weibotuiAuth->isAuthenticated())
            {
                $weiboTokenAndProfiles = SocialBusiness::getWeiboTokensAndProfilesFromDbByUserID($weibotuiAuth->getData('user_id'));

                foreach ($weiboTokenAndProfiles as &$weiboTokenAndProfile)
                {
                    //_DEBUG($weiboTokenAndProfile,'weiboTokenAndProfile');
                    if(($weiboTokenAndProfile['weibo_uid'] == $uid)
                        && ($weiboTokenAndProfile['weibo_type'] == \WBT\Model\Weibotui\WeiboType::SINA))
                    {
                        $is_owner = true;
                        break;
                    }
                }
            }
            else
            {
                $weiboTokenAndProfiles = [];
            }

        }
        else if($loginType === AuthBusiness::AUTH_SOCIAL_ONLY)
        {
            $cur_login_uid = App::getInstance()->auth('weibo')->getData('uid');
            if($uid == $cur_login_uid )
            {
                $is_owner = true;
            }else
            {
                log_warning("cur_login_uid($cur_login_uid) != uid($uid)");
            }
        }
        else
        {
            log_warning("unkown login type: $loginType");
        }
        return $is_owner;
    }

    // 根据uid获得用户对应的weibo client，
    public static function getWeiboClient($uid)
    {
        $res = array('errno' => 0);

        $weiboData = SocialBusiness::getWeiboTokenAndProfileFromDbByWeiboUID($uid, \WBT\Model\Weibotui\WeiboType::SINA);
        if(empty($weiboData))
        {
            $error = "getWeiboTokenAndProfileFromDbByWeiboUID($id) failed.";
            $res['errno'] = 1;
            $res['errno'] = $error;
            log_error($error);
            return $res;
        }
        $c = WeiboClientFactory::createFromKeySecret($weiboData['app_key'], $weiboData['app_secret'],$weiboData['access_token']);
        $res['client'] = $c;
        return $res;
    }

    // 目前是固定的appkey
    public static function  getAppKey($uid)
    {
        $appKey = _C("config.weibo.weibotui.appKey");
        return $appKey;
    }

    /* 添加定时微博
     * todo : 需要返回微博json ，以便在前端及时显示该定时微博
     * @param string uid
     * @param string text
     * @param string imageUrl
     * @param int rtWeiboID
     * @param datetime dingshiTime
     */
    public static function addDingshiWeibo($uid, $text, $imageUrl, $rtWeiboID, $dingshiTime)
    {
        $res = array('errno' => 0, SinaDingshiWeibo::SINA_DINGSHI_WEIBO_ID => 0);

        // 将时间转换成字符串格式
        $dingshiTime = datetime_to_str($dingshiTime);

        $weibo = new SinaDingshiWeibo([ SinaDingshiWeibo::UID => $uid, SinaDingshiWeibo::SEND_TIME =>$dingshiTime ]);
        if(!$weibo->isEmpty())
        {
            $error = '同一时间只能发布一个定时微博';
            $res['errno'] = 1;
            $res['errer'] = $error;
            log_error("[uid:$uid][dingshiTime:$dingshiTime][image_url:$imageUrl][rtWeiboID:$rtWeiboID][error:$error][text:$text]");
            return $res;
        }

        $weibo->setUID($uid)
              ->setText($text)
              ->setImageUrl($imageUrl)
              ->setSendTime($dingshiTime)
              ->setStatus(DingshiWeiboStatus::TOSEND)
              ->setAppKey(self::getAppKey($uid));

        $weibo->insert();

        log_info("[sina_dingshi_weibo_id:{$weibo->getSinaDingshiWeiboID()}][uid:$uid][dingshiTime:$dingshiTime][image_url:$imageUrl][rtWeiboID:$rtWeiboID][text:$text]");

        return $res;
    }

    /* 更新定时微博
     * @param int dingshiWeiboID
     * @param string text
     * @param datetime dingshiTime
     */
    public static function updateDingshiWeibo($dingshiWeiboID, $text, $dingshiTime)
    {
        $res = ['errno' => 0];
        $weibo = new SinaDingshiWeibo([ SinaDingshiWeibo::SINA_DINGSHI_WEIBO_ID => $dingshiWeiboID]);
        if($weibo->isEmpty())
        {
            $res['errno'] = 1;
            $res['errer'] = '未找到定时微博';
            return $res;
        }

        $weibo->setText($text)
              ->setStatus(DingshiWeiboStatus::TOSEND)
              ->setErrno(0)
              ->setError('')
              ->setSendTime($dingshiTime);

        $weibo->save();

        return $res;
    }

    // 获取定时微博状态，返回null表示没有找到对应的定时微博
    public static function getDingshiWeiboStatus($dingshiWeiboID)
    {
        $weibo = new SinaDingshiWeibo([ SinaDingshiWeibo::SINA_DINGSHI_WEIBO_ID => $dingshiWeiboID]);
        return $weibo->getStatus();
    }

    /* 获取指定时间范围需要发送的定时微博,返回id数组
     * @param datetime startTime
     * @param datetime endTime
     */
    public static function getDingshiWeiboToSendAndUpdateStatusToSending($startTime, $endTime)
    {
        $dingshiWeiboIdArray = [];

        $sql_condition = sprintf("status in ('tosend','sentfailed') and  '%s' < send_time and send_time <= '%s'",
            datetime_to_str($startTime),datetime_to_str($endTime));

        $condition = [new DbCondition($sql_condition)];

        $dingshiWeiboIdArray = SinaDingshiWeibo::fetchColumn(SinaDingshiWeibo::SINA_DINGSHI_WEIBO_ID,$condition);

        if(empty($dingshiWeiboIdArray) )
        {
            return  $dingshiWeiboIdArray;
        }

        $weibo  = new SinaDingshiWeibo();

        $weibo->setStatus(DingshiWeiboStatus::SENDING)->update([SinaDingshiWeibo::SINA_DINGSHI_WEIBO_ID => $dingshiWeiboIdArray]);

        return $dingshiWeiboIdArray;
    }

    // 根据定时微博id发送定时微博
    public static function sendDingWeibo($dingshiWeiboID)
    {
        $weibo = new SinaDingshiWeibo([SinaDingshiWeibo::SINA_DINGSHI_WEIBO_ID => $dingshiWeiboID]);
        if($weibo->isEmpty())
        {
            $error = "counld not find dignshiweibo. id=$dingshiWeiboID";
            log_error($error);
            $res['errno'] = 1;
            $res['errno'] = $error;
            log_info("[dingshiWeiboID:$dingshiWeiboID][uid:{$weibo->getUID()}][errno:{$res['errno']}][error:{$res['error']}]");
            return ;
        }

        $_SERVER["REMOTE_ADDR"] = $weibo->getIPAddress();
        $res =  self::sendWeibo($weibo->getUID(),$weibo->getText(), $weibo->getImageUrl(), $weibo->getRtID());

        $weibo->setErrno($res['errno']);
        $weibo->setError($res['error']);
        $status =  $res['errno'] ? DingshiWeiboStatus::SENTFAILED : DingshiWeiboStatus::SENTOK;
        $weibo->setStatus($status);
        $weibo->setWeiboUrl($res['weibo_url']);
        $weibo->save();

        log_info("[dingshiWeiboID:$dingshiWeiboID][uid:{$weibo->getUID()}][weibo_url:{$res['weibo_url']}][errno:{$res['errno']}][error:{$res['error']}]");
        return $res;
    }


    /* 发送微博
     * @param text : 发送的文本内容
     * @param uid : 新浪微博用户id
     * @param rtWeiboID : 要转发的微博id，原创微博不用设置此参数
     * @param imageUrl : 微博配图url，
     */
    public static function sendWeibo($uid, $text, $imageUrl=null, $rtWeiboID=0)
    {
        $res = ['errno' => 0,'error'=>'','weibo_url'=>''];

        $res_client = self::getWeiboClient($uid);
        if($res_client['errno'] !== 0)
        {
            $res['errno'] = 1;
            $res['error'] = '内部错误';
            log_error("getWeiboClient($uid) failed. errno=1");
            return $res;
        }

        // 原创微博
        if(empty($rtWeiboID))
        {
            if(empty($imageUrl))
            {
                $api_res = $res_client['client']->update($text);
            }else
            {
                $api_res = $res_client['client']->upload($text, $imageUrl);
            }
        }else // 转发微博
        {
            $api_res = $res_client['client']->repost($rtWeiboID,$text);
        }

        if(isset($api_res['error_code']) && $api_res['error_code'] != 0 ){
            $error = "weibo update failed. [error_code: {$api_res['error_code']}][error: {$api_res['error']}][text:$text]";
            $res['errno'] = 1;
            $res['error'] = $error;
            log_error($error);
            return $res;
        }
        $res['weibo_url'] = "http://api.t.sina.com.cn/$uid/statuses/{$api_res['id']}";
        return $res;
    }

    public static function checkWeiboServiceResult($result)
    {
        if (empty($result))
        {
            throw new \Bluefin\Exception\VendorServiceException(
                _APP_("Weibo API returned empty result.")
            );
        }

        if (is_array($result) && array_key_exists('error_code', $result))
        {
            throw new \Bluefin\Exception\VendorServiceException(
                _T($result['error_code'], 'sina_weibo')
            );
        }
    }
}
