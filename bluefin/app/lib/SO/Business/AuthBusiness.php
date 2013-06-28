<?php

namespace SO\Business;

use Bluefin\App;
use SO\Business\SinaWeiboBusiness;
use SO\Business\QQWeiboBusiness;
use SO\Business\SocialBusiness;
use WBT\Model\Weibotui\WeiboType;
use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\WeiboToken;
use Common\Data\Event;

class AuthBusiness
{
    const SNA_BINDING = 'binding';

    /**
     * 通过社交账号登录系统。
     *
     * @param $type
     * @param array $authParams
     * @param null $state
     * @return int
     * @throws \Exception
     */
    public static function login($type, array $authParams, $state = null)
    {
        $loginProfile = self::getLoginProfile();

        if (isset($loginProfile) && $loginProfile['weibotui'])
        {
            $cachedLoginProfile = $loginProfile;
        }

        self::logoutSNAccounts();

        $isBinding = $state == self::SNA_BINDING;

        $snaAuth = App::getInstance()->auth($type);
        $flag = $snaAuth->authenticate($authParams);
        $weibo = null;

        if ($flag === \Bluefin\Auth\AuthHelper::SUCCESS)
        {//成功登录
            $authData = $snaAuth->getData();
            $weiboData = null;
            $uid = null;

            switch ($type)
            {
                case WeiboType::WEIBO:
                    $uid = $authData['uid'];
                    $weiboData = SinaWeiboBusiness::getLoggedInWeiboData($authData['access_token'], $uid);
                    break;

                case WeiboType::QQ_WEIBO:
                    $weiboProfile = QQWeiboBusiness::getWeiboProfileByToken($authData);
                    QQWeiboBusiness::checkWeiboServiceResult($weiboProfile);
                    $weiboProfileJSON = json_encode($weiboProfile, true);

                    $uid = $authData['uid'];
                    $weiboUrl = $weiboProfile['data']['homepage'];
                    $displayName = $weiboProfile['data']['nick'];
                    $avatarSmall = $weiboProfile['data']['head'] . '/100';
                    break;
            }

            $db = App::getInstance()->db('weibotui')->getAdapter();
            $db->beginTransaction();

            try
            {
                $weibo = new Weibo();
                $weibo->reset($weiboData)
                    ->save();

                $snaID = $weibo->getWeiboID();

                SocialBusiness::recordSnaLogin($snaID);

                $weiboToken = new WeiboToken();
                $weiboToken->reset($authData);
                $weiboToken
                    ->setWeibo($snaID)
                    ->save();

                $db->commit();
            }
            catch (\Exception $e)
            {
                $db->rollback();

                throw $e;
            }

            $weiboUserID = $weibo->getUser();

            if ($isBinding && isset($cachedLoginProfile))
            {//Client端已登录
                $userID = $cachedLoginProfile['user_id'];

                if (isset($weiboUserID))
                {//微博已经有绑定用户
                    if ($weiboUserID == $userID)
                    {
                        //已绑定的是登录的用户
                        $flag = Event::I_SNA_ALREADY_BIND;
                    }
                    else
                    {
                        //已绑定别的用户
                        $flag = Event::E_SNA_BIND_OTHER;
                    }
                }
                else
                {
                    SocialBusiness::bind($snaID, $userID);

                    $flag = Event::S_SNA_BIND;
                }

                self::logoutSNAccounts();
                App::getInstance()->session()->set('login_profile', $cachedLoginProfile);

                return [$flag, $weibo];
            }
            //Client端未登录

            if (isset($weiboUserID))
            {//有绑定，进行登录
                self::setWeibotuiLoginProfile(WBTBusiness::getClient()->client_login($weiboUserID));

                return [Event::S_SNA_LOGIN_SUCCESS, $weibo];
            }

            //未绑定，也无登录用户
            $flag = Event::I_SNA_UNBIND;

            $loginProfile = [
                'weibotui' => false,
                'sna_id' => $snaID,
                'type' => $type,
                'uid' => $uid,
                'display_name' => $weiboData[Weibo::DISPLAY_NAME],
                'avatar' => $weiboData[Weibo::AVATAR_S]
            ];

            App::getInstance()->session()->set('login_profile', $loginProfile);
        }

        return [$flag, $weibo];
    }

    public static function getAllSupportedSNAuth()
    {
        $redirectUrl = App::getInstance()->gateway()->path();

        $snAuth = ['weibo','qq_weibo'];
        $authInfo = [];

        foreach ($snAuth as $provider)
        {
            $auth = App::getInstance()->auth($provider);
            $authInfo[$provider] = [
                'name' => _APP_('auth.' . $provider),
                'entry' => $auth->getAuthUrl($redirectUrl, true, self::SNA_BINDING)
            ];
        }

        return $authInfo;
    }

    public static function getLoginProfile()
    {
        return App::getInstance()->session()->get('login_profile');
    }

    public static function setWeibotuiLoginProfile(array $weibotuiProfile)
    {
        self::logoutSNAccounts();

        $loginProfile = [
            'weibotui' => true,
            'user_id' => $weibotuiProfile['user_id'],
            'username' => $weibotuiProfile['username'],
            'display_name' => $weibotuiProfile['profile_nick_name'],
            'avatar' => $weibotuiProfile['profile_avatar']
        ];

        App::getInstance()->session()->set('login_profile', $loginProfile);

        return $loginProfile;
    }

    public static function isLoggedIn()
    {
        $loginProfile = self::getLoginProfile();
        return !empty($loginProfile);
    }

    public static function isWeibotuiLoggedIn()
    {
        $loginProfile = self::getLoginProfile();
        return isset($loginProfile) && $loginProfile['weibotui'];
    }

    public static function isSnaLoggedIn()
    {
        $loginProfile = self::getLoginProfile();
        return isset($loginProfile) && !$loginProfile['weibotui'];
    }

    public static function logoutSNAccounts()
    {
        $authList = App::getInstance()->session()->get('auth');

        if (isset($authList))
        {
            foreach ($authList as $authName)
            {
                App::getInstance()->auth($authName)->clearIdentity();
                App::getInstance()->role($authName)->clear();
            }
        }

        App::getInstance()->session()->remove('auth');
        App::getInstance()->session()->remove('login_profile');
    }

    public static function logout()
    {
        self::logoutSNAccounts();
        WBTBusiness::getClient()->forgetAccessToken();
    }
}