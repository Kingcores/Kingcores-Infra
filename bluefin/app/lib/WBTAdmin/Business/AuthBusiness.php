<?php

namespace WBTAdmin\Business;

use Bluefin\App;

use WBT\Model\Weibotui\AdminLoginRecord;
use WBT\Model\Weibotui\AdminWithRole;
use WBT\Model\Weibotui\Admin;
use WBT\Model\Weibotui\UserStatus;

class AuthBusiness
{
    /**
     * 登录管理系统。
     *
     * @param array $authInput
     * @return int
     */
    public static function login(array $authInput)
    {
        self::logout();

        $wbtAdmin = App::getInstance()->auth('wbt_admin');

        $flag = $wbtAdmin->authenticate($authInput);

        if ($flag !== \Bluefin\Auth\AuthHelper::SUCCESS)
        {
           return $flag;
        }

        if ($wbtAdmin->getData(Admin::STATUS) != UserStatus::ACTIVATED)
        {
            self::logout();
            return \Bluefin\Auth\AuthHelper::FAILURE_STATUS_INVALID;
        }

        $adminLoginRecord = new AdminLoginRecord();
        $adminLoginRecord->insert();

        App::getInstance()->role('wbt_admin')->save(AdminWithRole::fetchColumn(AdminWithRole::ROLE, [ AdminWithRole::ADMIN => $wbtAdmin->getUniqueID() ]));

        return \Bluefin\Auth\AuthHelper::SUCCESS;
    }

    public static function refreshProfileAndRole()
    {
        $wbtAdmin = App::getInstance()->auth('wbt_admin');
        $wbtAdmin->refresh();

        App::getInstance()->role('wbt_admin')->save(AdminWithRole::fetchColumn(AdminWithRole::ROLE, [ AdminWithRole::ADMIN => $wbtAdmin->getUniqueID() ]));
    }

    /**
     * 注销所有账号。
     */
    public static function logout()
    {
        App::getInstance()->auth('wbt_admin')->clearIdentity();
        App::getInstance()->role('wbt_admin')->clear();
    }
}
