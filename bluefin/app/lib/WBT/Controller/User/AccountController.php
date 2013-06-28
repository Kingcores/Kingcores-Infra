<?php

namespace WBT\Controller\User;

use Bluefin\App;
use Common\Data\Event;
use WBT\Controller\WBTControllerBase;
use WBT\Business\UserBusiness;
use WBT\Business\AuthBusiness;
use WBT\Business\SsoBusiness;
use WBT\Model\Weibotui\WeiboType;

class AccountController extends WBTControllerBase
{
    public function indexAction()
    {
    }

    public function bindAction()
    {
        $auth = $this->_requireAuth('weibotui');

        $type = $this->_request->getQueryParam('type');
        _ARG_IS_SET(_META_('weibotui.weibo.type'), $type);

        if (!(new \WBT\Model\Weibotui\WeiboType())->validate($type))
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $uid = $this->_request->getQueryParam('id');
        _ARG_IS_SET(_META_('weibotui.weibo.uid'), $uid);

        $weibo = UserBusiness::bindWeiboToWeibotui($auth->getUniqueID(), $type, $uid);

        $this->_redirectWithEvent(Event::success(Event::SRC_SNA, Event::S_SNA_BIND), ['%type%' => _META_('weibotui.weibo_type.' . $type), '%sna_name%' => $weibo->getDisplayName(), '%username%' => $auth->getData('username')],null, ['_ticket' => SsoBusiness::issueUserTicket($auth->getUniqueID())]);
    }

    public function homeAction()
    {
        if(UserBusiness::isAdvertiser())
        {
            $this->_gateway->redirect(_R('m-c-a', ['home', 'advertiser', 'index']));
        }
        elseif(UserBusiness::isTuike())
        {
            $this->_gateway->redirect(_R('m-c-a', ['home', 'tuike', 'index']));
        }
        else
        {
            $this->_gateway->redirect(App::getInstance()->rootUrl());
        }
    }

    public function settingAction()
    {
        $loginProfile = $this->_requireWBTLoginProfile();


        $roles = array_try_get($loginProfile, 'user_roles', []);

        if (in_array(\WBT\Model\Weibotui\UserRole::TUIKE, $roles))
        {
            $this->_gateway->redirect(_R('m-c-a', ['home', 'tuike', 'change_user_info']));
        }

        if (in_array(\WBT\Model\Weibotui\UserRole::ADVERTISER, $roles))
        {
            $this->_gateway->redirect(_R('m-c-a', ['home', 'advertiser', 'change_user_info']));
        }

        throw new \Bluefin\Exception\PageNotFoundException();
    }
}