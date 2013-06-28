<?php

namespace WBT\Controller\Home;

use WBT\Controller\WBTControllerBase;
use WBT\Model\Weibotui\UserStatus;
use WBT\Business\UserBusiness;

use Common\Data\Event;

class WeibotuiController extends WBTControllerBase
{
    /**
     * @var \Bluefin\Auth\AuthInterface
     */
    protected $_auth;

    protected function _init()
    {
        parent::_init();

        //要求是微博推用户身份
        $this->_requireWeibotuiLoginRole();
    }

    protected function _requireWeibotuiLoginRole()
    {
        $this->_auth = $this->_requireAuth('weibotui');

        $this->_setUserProfileAndRolesInView();
        $this->_view->set('userAsset', UserBusiness::getUserAsset($this->_auth->getUniqueID()));
    }

    public function indexAction()
    {
        $this->_validateAccountStatus($this->_auth);
    }

    public function nonactivatedAction()
    {

    }

    public function activateAction()
    {
        $username = $this->_auth->getData('username');
        UserBusiness::sendVerificationEmail($username);
        $this->_redirectWithSource('register/verify_email', ['email' => $username]);
    }
}
