<?php

namespace WBT\Business;

use Bluefin\App;
use WBT\Business\MailBusiness;
use WBT\Model\Weibotui\User;
use WBT\Model\Weibotui\Tuike;
use WBT\Model\Weibotui\UserRole;
use WBT\Model\Weibotui\UserWithRole;
use WBT\Model\Weibotui\UserStatus;
use WBT\Model\Weibotui\UserActiveRecord;
use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\WeiboToken;
use WBT\Model\Weibotui\LoginType;
use WBT\Model\Weibotui\PersonalProfile;
use WBT\Model\Weibotui\UserDepositRecord;
use WBT\Model\Weibotui\UserExpenseRecord;
use WBT\Model\Weibotui\UserIncomeRecord;
use Common\Data\Event;

class UserBusiness
{
    /**
     * [REVIEWED]
     * @param $userID
     * @param $role
     * @return string
     */
    public static function getHomePageByRole($userID, $role)
    {
        if (!isset($role))
        {
            $roles = App::getInstance()->role('weibotui')->get();
            if (isset($roles) && count($roles) == 1)
            {
                $role = $roles[0];
            }
        }

        $homePage = [
            UserRole::SN_USER => 'http://so.weibotui.com',
            UserRole::TUIKE => 'http://www.weibotui.com/home/tuike/index',
            UserRole::ADVERTISER => 'http://www.weibotui.com/home/advertiser/index',
            UserRole::STAFF => 'http://www.weibotui.com',
        ];

        if (!isset($role) || !array_key_exists($role, $homePage))
        {
            $url = 'http://www.weibotui.com/home/weibotui/index';
        }
        else
        {
            $url = $homePage[$role];
        }

        if ($role == UserRole::SN_USER)
        {
            $url = build_uri($url, ['_ticket' => SsoBusiness::issueUserTicket($userID)]);
        }

        return $url;
    }

    /**
     * [REVIEWED]
     * @param array $condition
     * @return array|null
     */
    public static function getUserProfile(array $condition)
    {
        $filters = [ User::USER_ID, User::USERNAME ];
        $condition = array_get_all($condition, $filters);

        return self::extractCommonUsedUserProfiles(User::fetchOneRow(['*', 'profile.*'], $condition));
    }

    /**
     * [REVIEWED]
     * @param null $sessionID
     * @return array|null
     */
    public static function getUserProfileFromSession($sessionID = null)
    {
        if (isset($sessionID))
        {
            $sessionData = App::getInstance()->cache('session')->get($sessionID);
            session_decode($sessionData);
        }

        $userProfile = App::getInstance()->session('auth.weibotui')->get();

        return self::extractCommonUsedUserProfiles($userProfile);
    }

    /**
     * [REVIEWED]
     * @param array $userProfile
     * @return array|null
     */
    public static function extractCommonUsedUserProfiles(array $userProfile)
    {
        if (empty($userProfile))
        {
            return null;
        }

        $profile = array_get_all($userProfile, [
            'user_id', 'username', 'preferences', 'status',
            'profile_nick_name', 'profile_display_name',
            'profile_photo', 'profile_avatar',
            'profile_gender', 'profile_homepage',
            'profile_description',
        ]);

        $profile['preferences'] = json_decode($profile['preferences'], true);

        return $profile;
    }

    /**
     * [REVIEWED]
     * @param $userID
     * @param array $profiles
     * @throws \Exception
     */
    public static function registerTuike($userID, array $profiles)
    {
        $db = App::getInstance()->db('weibotui')->getAdapter();
        $db->beginTransaction();

        try
        {
            $profile = new PersonalProfile([PersonalProfile::USER => $userID]);
            $profile->apply($profiles)
                ->update();

            $tuike = new Tuike();
            $tuike->setUser($userID)
                ->insert();

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();

            throw $e;
        }
    }

    /**
     * [REVIEWED]
     * @param $email
     */
    public static function sendVerificationEmail($email)
    {
        $user = new User([User::USERNAME => $email]);
        _NON_EMPTY($user);

        if ($user->getStatus() !== UserStatus::NONACTIVATED)
        {
            return;
        }

        //uat是User Activation Token缩写
        $token = \Common\Helper\UniqueIdentity::generate(32);
        App::getInstance()->cache('l1')->set('uat:' . $token, $user->getUserID(), 24*3600);

        $activateUrl =  _U("register/activate?token={$token}");

        _DEBUG($activateUrl);

        $subject = '微博推（www.weibotui.com）用户邮件地址确认';
        $content = "尊敬的微博推用户<hr>欢迎您注册成为微博推(www.weibotui.com)的用户。<br>请您在24小时内点击以下地址来激活您的帐户：<br><a href='{$activateUrl}'>$activateUrl</a><br>如果以上链接无法点击，请将它复制到浏览器的地址栏中打开。<br>如果您有任何问题，请通过<a href='http://e.weibotui.com/weibotui'>@微博推</a> 与我们联系。<br>感谢您的支持！";

        MailBusiness::sendMail($email, $subject, $content);
    }

    /**
     * [REVIEWED]
     * @static
     * @param $token 用户令牌
     * @return bool  返回ture表示正常结果，false表示激活失败
     * @throws \Exception
     */
    public static function activateUser($token)
    {
        $userId = App::getInstance()->cache('l1')->get('uat:' . $token);
        App::getInstance()->cache('l1')->remove('uat:' . $token);

        if (!isset($userId))
        {
            return Event::error(Event::SRC_REG, Event::E_INVALID_TOKEN);
        }

        $db = App::getInstance()->db('weibotui')->getAdapter();
        $db->beginTransaction();

        try
        {
            // 激活用户
            $user = new User($userId);
            _NON_EMPTY($user);

            if ($user->getStatus() === UserStatus::ACTIVATED)
            {
                $db->rollback();
                return Event::info(Event::SRC_REG, Event::I_ACCOUNT_ALREADY_ACTIVATED);
            }

            if ($user->getStatus() === UserStatus::DISABLED)
            {
                $db->rollback();
                return Event::error(Event::SRC_AUTH, Event::E_ACCOUNT_DISABLED);
            }

            $user->setStatus(UserStatus::ACTIVATED);
            $user->setActivatedTime(time());
            $user->save();

            $profile = new PersonalProfile($user->getProfile());
            if ($profile->getEmail() == $user->getUsername())
            {
                $profile->setEmailVerified(true)->save();
            }

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();
            throw $e;
        }

        return Event::success(Event::SRC_REG, Event::S_ACTIVATE_SUCCESS);
    }

    public static function getRegisteredUserByRequestToken($requestToken)
    {
        //User Registration Token
        $userId = App::getInstance()->cache('l1')->get('urt:' . $requestToken);

        return $userId;
    }

    public static function isUserExists($username)
    {
        $user = new User([User::USERNAME => $username]);
        return !$user->isEmpty();
    }

    /**
     * [REVIEWED]
     * @param $username
     * @param $password
     * @throws \Bluefin\Exception\DataException
     * @throws \Exception
     */
    public static function registerWeibotui($username, $password, $requestToken = null)
    {
        $db = App::getInstance()->db('weibotui')->getAdapter();
        $db->beginTransaction();

        try
        {
            $user = new User([User::USERNAME => $username]);
            if (!$user->isEmpty())
            {
                throw new \Bluefin\Exception\DataException(_APP_('Duplicate entry "%value%" as [%name%].', [  '%name%' => _META_('weibotui.user.username'), '%value%' => $username ]));
            }

            $user->setUsername($username)
                ->setPassword($password)
                ->insert();

            $nickName = substr($username, 0, strpos($username, '@'));
            $profile = new PersonalProfile();
            $profile->setUser($user->getUserID())
                ->setNickName($nickName)
                ->setEmail($username)
                ->setAvatar('/images/avatar_default2.png')
                ->insert();

            $user->setProfile($profile->getPersonalProfileID())
                ->update();

            if (isset($requestToken))
            {
                App::getInstance()->cache('l1')->set('urt:' . $requestToken, $user->getUserID(), 24*3600);
            }

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();

            throw $e;
        }
    }

    /**
     * 绑定微博到微博推账号。
     * [REVIEWED]
     *
     * @param $userID
     * @param $type
     * @param $weiboUID
     * @return \WBT\Model\Weibotui\Weibo
     * @throws \Exception
     */
    public static function bindWeiboToWeibotui($userID, $type, $weiboUID)
    {
        $db = App::getInstance()->db('weibotui')->getAdapter();
        $db->beginTransaction();

        try
        {
            $weibo = new Weibo([Weibo::TYPE => $type, Weibo::UID => $weiboUID]);
            _NON_EMPTY($weibo);

            $weibo->setUser($userID);
            $weibo->save();

            $userWithRole = new UserWithRole();
            $userWithRole->setUser($userID)
                ->setRole(UserRole::SN_USER)
                ->insert(true);

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();

            throw $e;
        }

        return $weibo;
    }

    /**
     * 通过新浪微博注册微博推账号。
     * [REVIEWED]
     *
     * @param $username 用户名
     * @param $password 密码
     * @param $type
     * @param $weiboUID 微博ID
     * @return \WBT\Model\Weibotui\Weibo
     * @throws \Bluefin\Exception\InvalidRequestException
     * @throws \Exception
     */
    public static function registerWeibotuiFromSocial($username, $password, $type, $weiboUID)
    {
        $db = App::getInstance()->db('weibotui')->getAdapter();
        $db->beginTransaction();

        try
        {
            $weibo = new Weibo([Weibo::TYPE => $type, Weibo::UID => $weiboUID]);
            _NON_EMPTY($weibo);

            $originalUser = $weibo->getUser();
            if (isset($originalUser))
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _APP_('The social networking account has already bound to a weibotui account.')
                );
            }

            $user = new User();
            $user->setUsername($username)
                ->setPassword($password)
                ->setPreferences(json_encode(['home_role' => UserRole::SN_USER]))
                ->insert();

            $profile = new PersonalProfile();
            $profile->setUser($user->getUserID())
                ->setNickName($weibo->getDisplayName())
                ->setAvatar($weibo->getAvatarS())
                ->setPhoto($weibo->getAvatarL())
                ->setHomepage($weibo->getUrl())
                ->setGender($weibo->getGender())
                ->setEmail($username)
                ->setDescription($weibo->getDescription())
                ->insert();

            $user->setProfile($profile->getPersonalProfileID())
                ->update();

            $weibo->setUser($user->getUserID())
                ->update();

            $userWithRole = new UserWithRole();
            $userWithRole->setUser($user->getUserID())
                ->setRole(UserRole::SN_USER)
                ->insert();

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();

            throw $e;
        }

        AuthBusiness::login($user->getUserID());

        self::sendVerificationEmail($username);

        return $weibo;
    }

    public static function isPasswordRight($username, $password)
    {
        $user = new User([User::USERNAME => $username]);

        if ($user->isEmpty())
        {
            return false;
        }

        $tmpUser = new User();
        $tmpUser->reset($user->data(), true);
        $tmpUser->setPassword($password);

        $record = $tmpUser->filter();

        if ($user->getPassword() != $record['password'])
        {
            return false;
        }
        return true;
    }

    /**
     * @static 修改用户密码
     * @param $username
     * @param $password
     * @return bool 返回true表示设置成功，返回false表示设置失败
     */
    public static function changeUserPassword($username, $password)
    {
        $user = new User([User::USERNAME => $username]);
        if ($user->isEmpty())
        {
            return false;
        }

        $user->setPassword($password)->save();
        return true;
    }

    public static function getLoginUsername()
    {
        $username = null;
        try
        {
            $weibotuiAuth = App::getInstance()->auth('weibotui');

            $username =  $weibotuiAuth->getData('username');
        }
        catch(\Exception $e)
        {
            $username = null;
        }
        return $username;
    }



    public static function getLoginUser()
    {
        $username = self::getLoginUsername();
        return new User([User::USERNAME => $username]);
    }

    public static function isAdvertiser()
    {
        $roles = App::getInstance()->role('weibotui')->get();
        return in_array(UserRole::ADVERTISER, $roles);
    }

    public static function isTuike()
    {
        $roles = App::getInstance()->role('weibotui')->get();
        return in_array(UserRole::TUIKE, $roles);
    }

    public static function getUserAsset($userID)
    {
        $userAsset = new \WBT\Model\Weibotui\UserAsset($userID);
        return $userAsset->data();
    }

    public static function getUserDepositRecordList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [UserDepositRecord::STATUS, UserDepositRecord::UNPAID_TIME]);
        $condition[UserDepositRecord::USER] = $userID;

        $selection = ['*', 'transaction.*'];

        return UserDepositRecord::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            [UserDepositRecord::UNPAID_TIME => true],
            $paging,
            $outputColumns
        );
    }

    public static function getUserExpenseRecordList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [UserExpenseRecord::STATUS, UserExpenseRecord::PENDING_TIME]);
        $condition[UserExpenseRecord::USER] = $userID;

        $selection = ['*'];

        return UserExpenseRecord::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            [UserExpenseRecord::PENDING_TIME => true],
            $paging,
            $outputColumns
        );
    }

    public static function getUserIncomeRecordList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        // bugtofix : UserIncomeRecord::STATUS  未定义
        $condition = array_get_all($condition, [UserIncomeRecord::STATUS, UserDepositRecord::UNPAID_TIME]);
        $condition[UserDepositRecord::USER] = $userID;

        $selection = ['*', 'transaction.*'];

        return UserDepositRecord::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            [UserDepositRecord::UNPAID_TIME => true],
            $paging,
            $outputColumns
        );
    }

}
