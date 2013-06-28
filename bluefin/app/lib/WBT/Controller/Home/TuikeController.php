<?php

namespace WBT\Controller\Home;

use Bluefin\App;
use WBT\Controller\WBTControllerBase;
use WBT\Business\AuthBusiness;
use WBT\Business\UserBusiness;
use WBT\Model\Weibotui\WeiboOrder;
use WBT\Model\Weibotui\WeiboOrderStatus;
use WBT\Model\Weibotui\WeiboInventory;
use WBT\Business\TuikeBusiness;
use WBT\Model\Weibotui\WeiboCampaignType;
use WBT\Model\Weibotui\InventoryStatus;

use Bluefin\HTML\NavBar;
use Bluefin\HTML\Button;
use Bluefin\HTML\ButtonGroup;
use Bluefin\HTML\Table;

class TuikeController extends WBTControllerBase
{
    protected $_registerTuikeURL;

    protected function _init()
    {
        parent::_init();

        $this->_registerTuikeURL = $this->_gateway->path('register/tuike');

        $this->_requireTuikeLoginRole();
    }

    protected function _requireTuikeLoginRole()
    {
        $auth = $this->_requireAuth('weibotui');

        $this->_validateAccountStatus($auth);

        if (!UserBusiness::isTuike())
        {
            $this->_gateway->redirect($this->_registerTuikeURL);
        }

        $this->_setUserProfileAndRolesInView();
        $this->_view->set('userAsset', UserBusiness::getUserAsset($auth->getUniqueID()));
    }

    public function indexAction()
    {
        $this->_gateway->forward('weibo_order_list');
    }

    public function weiboOrderListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();
        //获取来源
        $from = array_try_get($condition, '_from', _R('m-c-a', ['index']), true);

        //获取状态参数
        $activeStatus = array_try_get($condition, WeiboOrder::STATUS, \Bluefin\Data\Database::KW_ALL_STATES);

        $navBar = new NavBar('我的推广任务');

        $allStatus = WeiboOrderStatus::getDictionary();
        $neededStatus = array_get_all($allStatus, [
            WeiboOrderStatus::PUBLISHED,
            WeiboOrderStatus::ACCEPTED,
            WeiboOrderStatus::SUBMITTED,
            WeiboOrderStatus::PAID
        ]);

        $navBar->addComponent(
            ButtonGroup::fromDictionary(
                $neededStatus,
                $activeStatus,
                _R(null, null, [WeiboOrder::STATUS => '']),
                true,
                'btn-primary',
                '状态',
                ButtonGroup::TYPE_RADIO
            )
        );

        $data = TuikeBusiness::getTuikeWeiboOrderList($auth->getUniqueID(), $condition, $paging, $outputColumns);

        $shownColumns = [
            'campaign_name' => [
                Table::COLUMN_VAR_TEXT => '<a href="#">{{this.campaign_name}}</a>'
            ],
            'campaign_type',
            'weibo_display_name' => [
                Table::COLUMN_VAR_TEXT => '<a href="{{this.weibo_url}}" target="_blank"><img src="{{weibo_avatar_s}}">{{this.weibo_display_name}}</a>'
            ],
            'campaign_start_time',
            'campaign_end_time',
            'status',
            'price',
            'operations' => [
                Table::COLUMN_OPERATIONS => [
                    new TableRowLink(
                        '接受任务',
                        "javascript:wbtAPI.call('weibo_order/do_accept', '_ID_', function(data){ bluefinBH.showInfo('" . _APP_('The marketing order is successfully accepted.') . "', function() { location.reload(); }); });",
                        function($row) { return WeiboOrder::isActionAllowed(WeiboOrder::TO_ACCEPT, $row); },
                        ['class' => 'btn btn-success']
                    ),
                    new TableRowLink(
                        '提交任务',
                        "javascript:bluefinBH.ajaxDialog('/form/weibo_order_submit/show?order=_ID_');",
                        function($row) { return WeiboOrder::isActionAllowed(WeiboOrder::TO_SUBMIT, $row); },
                        ['class' => 'btn btn-success']
                    ),
                    new TableRowLink(
                        '拒绝任务',
                        "javascript:wbtAPI.call('weibo_order/do_refuse', '_ID_', function(data){ bluefinBH.showInfo('" . _APP_('The marketing order is refused.') . "'); });",
                        function($row) { return WeiboOrder::isActionAllowed(WeiboOrder::TO_REFUSE, $row); },
                        ['class' => 'btn btn-inverse']
                    )
                ]
            ]
        ];

        $table = Table::fromDbData($data, $outputColumns, WeiboOrder::WEIBO_ORDER_ID, $paging, $shownColumns,
            ['class' => 'table-bordered table-striped table-hover']
        );

        $table->showRecordNo = true;

        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
    }

    public function weiboInventoryListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();
        //获取来源
        $from = array_try_get($condition, '_from', _R('m-c-a', ['index']), true);

        //获取状态参数
        $activeStatus = array_try_get($condition, WeiboInventory::STATUS, \Bluefin\Data\Database::KW_ALL_STATES);

        $campaignType = array_try_get($condition, WeiboInventory::TYPE, \Bluefin\Data\Database::KW_ALL_STATES);

        $navBar = new NavBar('我的渠道列表');
        $navBar->addComponent(
            ButtonGroup::fromDictionary(
                WeiboCampaignType::getDictionary(),
                $campaignType,
                _R(null, null, [WeiboInventory::STATUS => $activeStatus, WeiboInventory::TYPE => '']),
                true,
                'btn-primary',
                '类型',
                ButtonGroup::TYPE_RADIO
            )
        );

        $allStatus = InventoryStatus::getDictionary();
        $neededStatus = array_get_all($allStatus, [
            InventoryStatus::UNAUDIT,
            InventoryStatus::AVAILABLE,
            InventoryStatus::UNAVAILABLE
        ]);

        $navBar->addComponent(
            ButtonGroup::fromDictionary(
                $neededStatus,
                $activeStatus,
                _R(null, null, [WeiboInventory::TYPE => $campaignType, WeiboInventory::STATUS => '']),
                true,
                'btn-primary',
                '状态',
                ButtonGroup::TYPE_RADIO
            )
        );

        $data = TuikeBusiness::getTuikeWeiboInventoryList(
            $auth->getUniqueID(),
            $condition,
            $paging,
            $outputColumns
        );

        $shownColumns = [
            'weibo_type',
            'weibo_display_name' => [
                Table::COLUMN_VAR_TEXT => '<a href="{{this.weibo_url}}" target="_blank"><img src="{{this.weibo_avatar_s}}">{{this.weibo_display_name}}</a>'
            ],
            'type',
            'status',
            'original_price',
            'current_price'
        ];

        $table = Table::fromDbData($data, $outputColumns, WeiboInventory::WEIBO_INVENTORY_ID, $paging, $shownColumns,
            ['class' => 'table-bordered table-striped table-hover' ]
        );

        $table->showRecordNo = true;

        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
    }

    public function incomeRecordListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();
        //获取来源
        $from = array_try_get($condition, '_from', _R('m-c-a', ['index']), true);

        $navBar = new NavBar('收入记录');

        $data = UserBusiness::getUserDepositRecordList($auth->getUniqueID(),
            $condition,
            $paging,
            $outputColumns
        );

        $shownColumns = [
            'transaction_serial_no',
            'transaction_vendor_no',
            'transaction_payment_method',
            'amount',
            'status',
            '_time' => [
                Table::COLUMN_TITLE => _DICT_('time'),
                Table::COLUMN_VAR_TEXT => "{{this.status|.='_time'|context='this'}}"
            ],
            'operations' => [
                Table::COLUMN_OPERATIONS => [
                    new TableRowLink(
                        '详情',
                        "#",
                        null
                    ),
                    new TableRowLink(
                        '支付',
                        "javascript:callWBTService('user_deposit_record/do_pay', '_ID_');",
                        function($row) { return UserDepositRecord::isActionAllowed(UserDepositRecord::TO_PAY, $row); }
                    ),
                    new TableRowLink(
                        '取消',
                        "javascript:callWBTService('user_deposit_record/do_cancel', '_ID_');",
                        function($row) { return UserDepositRecord::isActionAllowed(UserDepositRecord::TO_CANCEL, $row); }
                    )
                ]
            ]
        ];

        $table = Table::fromDbData($data, $outputColumns, UserDepositRecord::USER_DEPOSIT_RECORD_ID, $paging, $shownColumns, ['class' => 'table-bordered table-striped table-hover' ]
        );

        $table->showRecordNo = true;

        $this->_view->set('location', ['advertiser', 'account', $this->_gateway->getActionName()]);
        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
    }

    public function snsAccountAction()
    {
        $loginProfile = $this->_requireWBTLoginProfile();

        $weibotuiAuth = App::getInstance()->auth('weibotui');
        if ($weibotuiAuth->isAuthenticated())
        {
            $weiboTokenAndProfiles = WeiboDbBusiness::getWeiboTokensAndProfilesFromDbByUserID($weibotuiAuth->getData('user_id'));

            foreach ($weiboTokenAndProfiles as &$weiboTokenAndProfile)
            {
                $weiboTokenAndProfile['weibo_profile'] = json_decode($weiboTokenAndProfile['weibo_profile'], true);
            }
        }
        else
        {
            $weiboTokenAndProfiles = [];
        }

        //_DEBUG($weiboTokenAndProfiles,'weiboTokenAndProfiles');

        $datetimePicker = new \Bluefin\HTML\DatetimePicker(null);

        $this->_view->set('auth', AuthBusiness::getAllSupportedAuthTypes(_R(), true, 'bind'));
        $this->_view->set('loginProfile', $loginProfile);
        $this->_view->set('weiboTokenProfiles', $weiboTokenAndProfiles);
        $this->_view->set('datetimePicker', $datetimePicker);
    }

    public function changePasswordAction()
    {
        if ($this->_request->isPost())
        {
            $from = $this->_request->getPostParam('from', App::getInstance()->basePath());

            $currentPassword = $this->_request->getPostParam('current_password');
            $newPassword = $this->_request->getPostParam('new_password');
            $passwordConfirm = $this->_request->getPostParam('password_confirm');

            if ($newPassword != $passwordConfirm)
            {
                $this->_view->error = '两次输入的密码不一致,请重新输入';
                $this->_transferPostStates();
                return;
            }

            $minPasswordLength = 6;
            if (strlen($newPassword) < $minPasswordLength )
            {
                $this->_view->error = '密码长度至少需要6位';
                $this->_transferPostStates();
                return;
            }

            $username = UserBusiness::getLoginUsername();

            if(!UserBusiness::isPasswordRight($username, $currentPassword))
            {
                $this->_view->error = '当前密码错误';
                $this->_transferPostStates();
                return;
            }

            UserBusiness::changeUserPassword($username, $newPassword);

            $this->_view->redirectMessage = '密码修改成功';
        }
        else
        {
            $this->_view->from = $this->_request->getQueryParam('from', App::getInstance()->basePath());
        }
    }


    public function changeUserInfoAction()
    {
        $user = UserBusiness::getLoginUser();
        if($user->isEmpty())
        {
            $this->_view->redirectMessage = '登录超时，请重新登录';
            return;
        }

        $username = $user->getUsername();
        if ($this->_request->isPost())
        {
            $alipay = $this->_request->getPostParam('alipay');
            $qq = $this->_request->getPostParam('qq');
            $mobile = $this->_request->getPostParam('mobile');

            $profile = new \WBT\Model\Weibotui\PersonalProfile([\WBT\Model\Weibotui\PersonalProfile::PERSONAL_PROFILE_ID => $user->getProfile()]);

            if($profile->isEmpty())
            {
                $this->_view->redirectMessage = '系统内部错误，帐户信息修改失败';
                log_error("profile is empty.username:$username");
                return;
            }

            if(!empty($qq))
            {
                $profile->setQQ($qq);
            }
            if(!empty($mobile))
            {
                $profile->setMobile($mobile);
            }

            $profile->save();

            $tuike = new \WBT\Model\Weibotui\Tuike([\WBT\Model\Weibotui\Tuike::USER => $username]);

            if($tuike->isEmpty())
            {
                $tuike->setUser($user->getUserID());
            }
            if(!empty($alipay))
            {
                $tuike->setAlipay($alipay)
                    ->save();
            }

            $this->_view->redirectMessage = '帐户信息修改成功';
        }
        else
        {
            $tuike = new \WBT\Model\Weibotui\Tuike([\WBT\Model\Weibotui\Tuike::USER => $user->getUserID()]);

            $this->_view->set('username', $username);
            $this->_view->set('alipay', $tuike->getAlipay());
            $this->_view->set('profile', UserBusiness::getUserProfile($username));

            $this->_view->from = $this->_request->getQueryParam('from', App::getInstance()->basePath());
        }

    }
}
