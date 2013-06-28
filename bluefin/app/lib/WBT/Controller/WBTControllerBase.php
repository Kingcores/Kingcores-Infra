<?php

namespace WBT\Controller;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Controller;
use Common\Data\Event;
use Common\Helper\BaseController;
use WBT\Business\AuthBusiness;
use WBT\Business\UserBusiness;
use WBT\Business\SsoBusiness;
use WBT\Model\Weibotui\User;
use WBT\Model\Weibotui\PersonalProfile;
use WBT\Model\Weibotui\UserStatus;

class WBTControllerBase extends BaseController
{
    protected function _validateAccountStatus($username, $accountStatus)
    {
        switch ($accountStatus)
        {
            case UserStatus::NONACTIVATED:
                $this->_redirectWithSource('register/verify_email', ['email' => $username, 'activate' => 1], true);
                break;

            case UserStatus::DISABLED:
                $this->_showEventMessage(Event::E_ACCOUNT_DISABLED, Event::SRC_AUTH);
                break;
        }
    }

    protected function _setUserProfileAndRolesInView()
    {
        $userProfiles = UserBusiness::getUserProfileFromSession();

        $this->_view->set('loginProfile', $userProfiles);
        $this->_view->set('userRoles', $this->_app->role('weibotui')->get());
    }
}
