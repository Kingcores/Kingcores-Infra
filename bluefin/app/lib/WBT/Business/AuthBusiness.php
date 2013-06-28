<?php

namespace WBT\Business;

use Bluefin\App;
use Common\Data\Event;
use WBT\Model\Weibotui\UserWithRole;
use WBT\Model\Weibotui\UserLoginRecord;
use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\WeiboToken;
use WBT\Model\Weibotui\LoginType;
use WBT\Model\Weibotui\WeiboType;
use WBT\Model\Weibotui\WeiboLoginRecord;
use WBT\Model\Weibotui\UserRole;
use Common\Helper\WeiboClientFactory;
use QQWeibo\QQWeiboClientFactory;
use WBT\Model\Weibotui\OAuthToken;
use WBT\Model\Weibotui\User;

class AuthBusiness
{
    /**
     * 获取系统所有支持的登录方式的登录入口相关信息。
     *
     * @param $redirectUrl 登录后要跳转到的地址。
     * @param bool $forceLogin
     * @param null $state
     * @return array
     */
    public static function getAllSupportedAuthTypes($redirectUrl, $forceLogin = false, $state = null)
    {
        isset($redirectUrl) || ($redirectUrl = _C('config.custom.url.mms_entry'));

        $thirdParty = ['weibo','qq_weibo'];
        $authInfo = array();

        foreach ($thirdParty as $provider)
        {
            $auth = App::getInstance()->auth($provider);
            $authInfo[$provider] = array(
                'name' => _APP_('auth.' . $provider),
                'entry' => $auth->getAuthUrl($redirectUrl, $forceLogin, $state)
            );
        }

        return $authInfo;
    }

    public static function clientLoginWeibotui($accessToken, $userID)
    {
        $token = new OAuthToken($accessToken);
        _NON_EMPTY($token);

        $token->setUser($userID)
            ->update();

        return UserBusiness::getUserProfile([User::USER_ID => $userID]);
    }

    /**
     * 通过微博推账号登录系统。
     *
     * @param array $authRequest
     * @return int
     */
    public static function login(array $authRequest)
    {
        self::logout();

        $auth = App::getInstance()->auth('weibotui');

        $flag = $auth->authenticate($authRequest);

        if ($flag !== \Bluefin\Auth\AuthHelper::SUCCESS)
        {
            return $flag;
        }

        self::loginUserDirectly($authRequest['username']);

        return \Bluefin\Auth\AuthHelper::SUCCESS;
    }

    public static function loginUserDirectly($username, $loginType = LoginType::WEIBOTUI)
    {
        $auth = App::getInstance()->auth('weibotui');

        $auth->setIdentity([User::USERNAME => $username]);

        App::getInstance()->role('weibotui')->reset(UserWithRole::fetchColumn(UserWithRole::ROLE, [UserWithRole::USER => $auth->getUniqueID()]));

        self::recordWeibotuiLogin($loginType);
    }

    public static function ssoLogin(array $authRequest)
    {
        $auth = App::getInstance()->auth('weibotui');

        return $auth->authenticate($authRequest);
    }

    /**
     * 保存微博推账号登录记录。
     * @param $sourceType 登录来源类型，可选值来自[WBT\Model\Weibotui\LoginType]。
     */
    public static function recordWeibotuiLogin($sourceType)
    {
        $userLoginRecord = new UserLoginRecord();
        $userLoginRecord->setType($sourceType);
        $userLoginRecord->insert();
    }

    public static function isUserHasRole($role)
    {
        $roles = App::getInstance()->role('weibotui')->get();
        return in_array($role, $roles);
    }

    public static function isLoggedIn()
    {
        return App::getInstance()->session('auth.weibotui')->has('user_id');
    }

    public static function getLoggedInUserId()
    {
        return App::getInstance()->session('auth.weibotui')->get('user_id');
    }

    public static function refreshLoggedInProfile()
    {
        $auth = App::getInstance()->auth('weibotui');
        $auth->refresh();
    }

    public static function refreshLoggedInUserRoles()
    {
        $auth = App::getInstance()->auth('weibotui');
        App::getInstance()->role('weibotui')->reset(UserWithRole::fetchColumn(UserWithRole::ROLE, [UserWithRole::USER => $auth->getUniqueID()]));
    }

    /**
     * 注销所有账号。
     */
    public static function logout()
    {
        App::getInstance()->auth('weibotui')->clearIdentity();
        App::getInstance()->role('weibotui')->clear();
    }
}
