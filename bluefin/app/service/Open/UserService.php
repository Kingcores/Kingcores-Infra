<?php

use Bluefin\App;
use Common\Helper\OAuthService;

use WBT\Business\SsoBusiness;
use WBT\Business\UserBusiness;
use WBT\Business\AuthBusiness;

class UserService extends OAuthService
{
    public function clientLogin()
    {
        $token = $this->_requireOAuthToken('sso');

        $userID = $this->_app->request()->getPostParam('user_id');
        _ARG_IS_SET(_APP_('api.open.user.client_login.user_id'), $userID);

        return AuthBusiness::clientLoginWeibotui($token['access_token'], $userID);
    }

    public function getUserProfile()
    {
        $token = $this->_requireOAuthToken('sso');

        $ticket = $this->_app->request()->getPostParam('ticket');
        if (isset($ticket))
        {
            $userID = SsoBusiness::getUserIdByTicket($ticket);
            if (!isset($userID))
            {
                throw new \Bluefin\Exception\InvalidRequestException("Invalid user ticket!");
            }

            $profile = UserBusiness::getUserProfile([\WBT\Model\Weibotui\User::USER_ID => $userID]);
            if (!empty($profile))
            {
                AuthBusiness::clientLoginWeibotui($token['access_token'], $profile[\WBT\Model\Weibotui\User::USER_ID]);
            }

            return $profile;
        }

        if (isset($token['user_id']))
        {
            return UserBusiness::getUserProfile([\WBT\Model\Weibotui\User::USER_ID => $token['user_id']]);
        }

        throw new \Bluefin\Exception\InvalidRequestException();
    }
}
