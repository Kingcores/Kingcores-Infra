<?php

namespace SO\Controller;

use Bluefin\App;
use WBT\Business\UserBusiness;
use SO\Business\AuthBusiness;
use SO\Business\WBTBusiness;
use WBT\Model\Weibotui\WeiboType;
use Common\Data\Event;
use Common\Helper\BaseController;

class AccountController extends BaseController
{
    protected function _init()
    {
        $this->_urlSignature = 'kl4nd';

        parent::_init();
    }

    public function loginAction()
    {
        $type = $this->_request->getQueryParam('type', null, true);
        _ARG_IS_SET(_META_('weibotui.weibo.type'), $type);

        if (!(new \WBT\Model\Weibotui\WeiboType())->validate($type))
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $loginParams = null;
        $state = null;
        empty($this->_requestSource) && ($this->_requestSource = $this->_gateway->path());

        switch ($type)
        {
            case WeiboType::WEIBO:
                //参数检测
                $code = $this->_request->getQueryParam('code');
                $state = $this->_request->getQueryParam('state');

                if (empty($code))
                {
                    $errorCode = $this->_request->getQueryParam('error_code');

                    if (isset($errorCode))
                    {
                        $this->_showEventMessage($errorCode, Event::SRC_SINA_WEIBO);
                        return;
                    }

                    throw new \Bluefin\Exception\InvalidRequestException();
                }

                //登录参数
                $loginParams = ['code' => $code, 'from' => $this->_requestSource];
                break;

            case WeiboType::QQ_WEIBO:
                $code = $this->_request->getQueryParam('code');
                $uid = $this->_request->getQueryParam('openid');

                if (empty($code) || empty($uid))
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }

                //登录参数
                $loginParams = ['code' => $code, 'uid' => $uid, 'from' => $this->_requestSource];
                break;
        }

        /**
         * @var \WBT\Model\Weibotui\Weibo $weibo
         */
        list($flag, $weibo) = AuthBusiness::login($type, $loginParams, $state);

        switch ($flag)
        {
            case Event::S_SNA_LOGIN_SUCCESS:
                $token = WBTBusiness::getClient()->accessToken();
                $this->_redirectWithSource(_C('config.custom.url.weibotui_sso'), ['access_token' => $token['access_token']]);
                break;

            case Event::I_SNA_UNBIND:
                $this->_redirectWithSource(_C('config.custom.url.register_by_sna'), ['type' => $type, 'id' => $weibo->getWeiboID()]);
                break;

            case Event::E_SNA_BIND_OTHER:
                $this->_redirectWithEvent(Event::error(Event::SRC_SNA, $flag));
                break;

            case Event::S_SNA_BIND:
                $loginProfile = AuthBusiness::getLoginProfile();
                $this->_redirectWithEvent(Event::success(Event::SRC_SNA, $flag), ['%type%' => _META_('weibotui.weibo_type.' . $type), '%sna_name%' => $weibo->getDisplayName(), '%username%' => $loginProfile['username']]);
                break;

            case Event::I_SNA_ALREADY_BIND:
                $this->_redirectWithEvent(Event::info(Event::SRC_SNA, $flag));
                break;

            default:
                $this->_redirectWithEvent(Event::error(Event::SRC_AUTH, $flag));
                break;
        }
    }

    public function sessionAction()
    {
        if (ENV != 'dev')
        {
            throw new \Bluefin\Exception\PageNotFoundException();
        }

        _ARR_DUMP($_SESSION);
    }

    public function logoutAction()
    {
        AuthBusiness::logout();
        $this->_gateway->redirect($this->_app->basePath());
    }
}
