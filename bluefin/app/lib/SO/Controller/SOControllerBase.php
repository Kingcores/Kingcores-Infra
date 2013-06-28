<?php

namespace SO\Controller;

use Bluefin\App;
use Bluefin\Convention;
use Common\Helper\BaseController;
use SO\Business\AuthBusiness;
use SO\Business\WBTBusiness;

class SOControllerBase extends BaseController
{
    protected function _init()
    {
        $ticket = $this->_request->getQueryParam('ticket', null, true);

        if (isset($ticket))
        {
            $loginProfile = null;

            try
            {
                $userProfiles = WBTBusiness::getClient()->get_user_profile_by_ticket($ticket);
                if (!empty($userProfiles))
                {
                    $loginProfile = AuthBusiness::setWeibotuiLoginProfile($userProfiles);
                }
            }
            catch (\Exception $e)
            {
                $this->_app->log()->error('SSO Error: ' . $e->getMessage());

                if ($this->_app->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
                {
                    $this->_app->log()->debug($e->getTraceAsString(), \Bluefin\Log::CHANNEL_DIAG);
                }
            }

            $uri = $this->_request->getRequestUri();
            $uri = build_uri($uri, ['ticket' => null]);

            if (isset($loginProfile))
            {
                $this->_gateway->redirect($uri);
            }
            else
            {

            }
        }
    }

    protected function _requireLoginProfile()
    {
        $loginProfile = AuthBusiness::getLoginProfile();

        if (empty($loginProfile))
        {
            $this->_app->log()->verbose('Redirected to sso entry page.', \Bluefin\Log::CHANNEL_DIAG);
            WBTBusiness::getClient()->singleSignOn($this->_request->getFullRequestUri());
        }

        $this->_view->set('loginProfile', $loginProfile);

        return $loginProfile;
    }
}
