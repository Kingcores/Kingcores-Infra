<?php

namespace WBT\Controller\Home;

use Bluefin\App;
use Bluefin\Data\Model;
use WBT\Controller\WBTControllerBase;
use WBT\Model\Weibotui\WeiboCampaign;
use WBT\Model\Weibotui\WeiboCampaignStatus;
use WBT\Model\Weibotui\WeiboOrder;
use WBT\Model\Weibotui\WeiboOrderStatus;
use WBT\Model\Weibotui\WeiboInventory;
use WBT\Model\Weibotui\UserDepositRecord;
use WBT\Model\Weibotui\UserExpenseRecord;
use WBT\Business\AuthBusiness;
use WBT\Business\UserBusiness;
use WBT\Business\AdvertiserBusiness;

use Bluefin\HTML\Button;
use Bluefin\HTML\ButtonGroup;
use Bluefin\HTML\NavBar;
use Bluefin\HTML\Table;
use Bluefin\HTML\Form;
use Bluefin\HTML\Link;

class AdvertiserController extends WBTControllerBase
{
    protected $_newAdvertiserURL;
    protected $_addAdvertiserRoleURL;

    protected function _init()
    {
        parent::_init();

        $this->_newAdvertiserURL = $this->_app->basePath() . 'user/account/new_advertiser';
        $this->_addAdvertiserRoleURL = $this->_app->basePath() . 'user/account/add_advertiser_role';

        //要求是广告主身份
        $this->_requireAdvertiserLoginRole();
    }

    protected function _requireAdvertiserLoginRole()
    {
        $auth = $this->_requireAuth('weibotui');

        $this->_validateAccountStatus($auth);

        if (!UserBusiness::isAdvertiser())
        {
            $this->_gateway->redirect($this->_addAdvertiserRoleURL);
        }

        $this->_setUserProfileAndRolesInView();
        $this->_view->set('userAsset', UserBusiness::getUserAsset($auth->getUniqueID()));
    }

    public function indexAction()
    {
        $this->_gateway->forward('weibo_campaign_list');
    }

    public function weiboCampaignListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();
        //获取来源
        $from = array_try_get($condition, '_from', _R('m-c-a', ['index']), true);

        //获取状态参数
        if (array_key_exists(WeiboCampaign::STATUS, $condition))
        {
            $activeStatus = $condition[WeiboCampaign::STATUS];
        }
        else
        {
            $activeStatus = \Bluefin\Data\Database::KW_ALL_STATES;
            $condition[WeiboCampaign::STATUS] = $activeStatus;
        }

        //导航栏
        $navBar = new NavBar('活动列表');
        $navBar->addComponents([
            new Button('<i class="icon-plus icon-white"></i> 新活动', "javascript:bluefinBH.ajaxDialog('/form/weibo_campaign/show');", ['class' => 'btn-success']),
            ButtonGroup::fromDictionary(
                WeiboCampaignStatus::getDictionary(),
                $activeStatus,
                _R(null, null, [WeiboCampaign::STATUS => '']),
                true,
                'btn-primary',
                '状态'
            )
        ]);

        //广告活动数据
        $data = AdvertiserBusiness::getUserWeiboCampaignList($auth->getUniqueID(), $condition, $paging, $outputColumns);

        //表单Header
        $shownOptions = [
            'name',
            'type' => [
                Table::COLUMN_CELL_STYLE => 'text-align:center;'
            ],
            'start_time' => [
                Table::COLUMN_VAR_TEXT => "{{this.start_time|date='Y-m-d H:i'}}",
                Table::COLUMN_CELL_STYLE => 'text-align:center;'
            ],
            'end_time'=> [
                Table::COLUMN_VAR_TEXT => "{{this.end_time|date='Y-m-d H:i'}}",
                Table::COLUMN_CELL_STYLE => 'text-align:center;'
            ],
            'order_status' => [
                Table::COLUMN_TITLE => _APP_('Order Status'),
                Table::COLUMN_VAR_TEXT => '总单数：{{this.total_order}} 结单数：{{this.paid_order}}<br>拒单数：{{this.refused_order}} 坏单数：{{this.bad_order}}'
            ],
            'cost' => [
                Table::COLUMN_TITLE => _APP_('Cost'),
                Table::COLUMN_VAR_TEXT => '预计支出：￥{{this.estimate_cost}}<br>实际支出：￥{{this.actual_cost}}'
            ],
            'status' => [
                Table::COLUMN_CELL_STYLE => 'text-align:center;',
            ],
            'operations' => [
                Table::COLUMN_OPERATIONS => [
                    new Link(
                        '查看详情',
                        '/home/advertiser/weibo_order_list?&campaign={{this.weibo_campaign_id}}&_from=' . b64_encode(_P()),
                        ['class' => 'btn btn-info']
                    ),
                    new Link(
                        '添加渠道',
                        '/home/advertiser/weibo_inventory_list?&campaign={{this.weibo_campaign_id}}&_from=' . b64_encode(_P()),
                        [
                            'class' => 'btn btn-success',
                            'visible' => function($row) { return WeiboCampaign::isActionAllowed(WeiboCampaign::TO_ADD_INVENTORY, $row); }
                        ]
                    )
                ]
            ]
        ];

        //构造表单
        $table = Table::fromDbData($data, $outputColumns, WeiboCampaign::WEIBO_CAMPAIGN_ID, $paging, $shownOptions, ['class' => 'table-bordered table-striped table-hover']
        );

        //显示记录编号，此编号是动态排序编号，和数据库中的数据无关
        $table->showRecordNo = true;

        $this->_view->set('location', ['advertiser', 'campaign', $this->_gateway->getActionName()]);
        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
    }

    public function weiboOrderListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();

        //获取状态参数
        $activeStatus = array_try_get($condition, WeiboOrder::STATUS, \Bluefin\Data\Database::KW_ALL_STATES);

        $weiboCampaignID = array_try_get($condition, 'campaign', null);

        if (!isset($weiboCampaignID))
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $weiboCampaign = new WeiboCampaign($weiboCampaignID);
        _NON_EMPTY($weiboCampaign);

        $navBar = new NavBar('已选的微博渠道');

        if (WeiboCampaign::isActionAllowed(WeiboCampaign::TO_ADD_INVENTORY, $weiboCampaign->data()))
        {
            $navBar->addComponent(new Button('<i class="icon-plus icon-white"></i> 新渠道', _R('m-c-a', ['weibo_inventory_list'], ['_from' => _R(), 'campaign' => $weiboCampaignID]), ['class' => 'btn btn-success']));
        }

        $navBar->addComponent(
            ButtonGroup::fromDictionary(
                WeiboOrderStatus::getDictionary(),
                $activeStatus,
                _R(null, null, [WeiboOrder::CAMPAIGN => $weiboCampaignID, WeiboOrder::STATUS => '']),
                true,
                'btn-primary',
                '状态',
                ButtonGroup::TYPE_DROPDOWN
            )
        );

        $data = AdvertiserBusiness::getUserWeiboOrderList($auth->getUniqueID(), $condition, $paging, $outputColumns);

        $shownColumns = [
            'weibo_display_name' => [
                Table::COLUMN_VAR_TEXT => '<a href="{{this.weibo_url}}" target="_blank"><img src="{{weibo_avatar_s}}">{{this.weibo_display_name}}</a>'
            ],
            'num_audience',
            'price',
            'finish_url' => [
                Table::COLUMN_VAR_TEXT => '<a href="{{this.finish_url}}">{{this.finish_url}}</a>',
                Table::COLUMN_DEPENDS_ON => 'finish_url'
            ],
            'snapshot_url' => [
                Table::COLUMN_VAR_TEXT => '<img src="{{this.snapshot_url}}" style="max-height: 50px;">',
                Table::COLUMN_DEPENDS_ON => 'snapshot_url'
            ],
            'status',
            'operations' => [
                Table::COLUMN_OPERATIONS => [
                    new Link(
                        '取消订单',
                        "javascript:wbtAPI.call('weibo_order/do_cancel', '{{this.weibo_order_id}}', function(data){ bluefinBH.showInfo('" . _APP_('The order [%order_id%] is cancelled.', ['%order_id%' => '#{{this.weibo_order_id}}']) . "', function() { location.reload(); }); });",
                        [
                            'class'=>'btn btn-inverse',
                            'visible'=>function($row) { return WeiboOrder::isActionAllowed(WeiboOrder::TO_CANCEL, $row); }
                        ]
                    ),
                    new Link(
                        '确认付款',
                        "javascript:bluefinBH.confirm('" . _APP_('Are you sure to pay the order?') . "', function() { javascript:wbtAPI.call('weibo_order/do_confirm', '{{this.weibo_order_id}}', function() { bluefinBH.showInfo('" . _APP_('The order [%order_id%] is paid.', ['%order_id%' => '#{{this.weibo_order_id}}']) . "', function() { location.reload(); }); }); });",
                        [
                            'class'=>'btn btn-danger',
                            'visible'=>function($row) { return WeiboOrder::isActionAllowed(WeiboOrder::TO_CONFIRM, $row); }
                        ]
                    )
                ]
            ]
        ];

        $table = Table::fromDbData($data, $outputColumns, WeiboOrder::WEIBO_ORDER_ID, $paging, $shownColumns,
            ['class' => 'table-bordered table-striped table-hover']
        );

        $table->showRecordNo = true;

        $publishSuccessMessage = _APP_('Your campaign is successfully published.');
        $publishButton = new Button('发布活动', "javascript:wbtAPI.call('weibo_campaign/do_publish', '{$weiboCampaignID}', function (data) {
            bluefinBH.showInfo('{$publishSuccessMessage}', function () { location.reload(); });
        });", ['class' => 'btn-primary']);
        $publishButton->visible = WeiboCampaign::isActionAllowed(WeiboCampaign::TO_PUBLISH, $weiboCampaign->data());

        $deleteButton = new Button('取消活动', "javascript:wbtAPI.call('weibo_campaign/do_cancel', '{$weiboCampaignID}');", ['class' => 'btn-inverse']);
        $deleteButton->visible = WeiboCampaign::isActionAllowed(WeiboCampaign::TO_CANCEL, $weiboCampaign->data());

        $operations = new \Bluefin\HTML\Container([$publishButton, $deleteButton]);

        if (isset($this->_requestSource))
        {
            $backButton = new Button('返回', $this->_requestSource);
            $operations->addComponent($backButton);
        }

        $this->_view->set('location', ['advertiser', 'campaign', $this->_gateway->getActionName()]);
        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
        $this->_view->set('operations', $operations);
    }

    public function weiboInventoryListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();

        $weiboCampaignID = array_try_get($condition, 'campaign', null, true);

        if (isset($weiboCampaignID))
        {
            $weiboCampaignData = AdvertiserBusiness::getUserWeiboCampaignDetail(
                $auth->getUniqueID(),
                $weiboCampaignID
            );

            if (empty($weiboCampaignData))
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _APP_('The campaign does not exist.')
                );
            }

            $selectedInventories = AdvertiserBusiness::getUserWeiboCampaignOrderIDs($weiboCampaignID);

            $condition[WeiboInventory::TYPE] = $weiboCampaignData[WeiboCampaign::TYPE];
            $condition[WeiboInventory::WEIBO_INVENTORY_ID] = new \Bluefin\Data\DbExprNot($selectedInventories);
        }

        $navBar = new NavBar('推客微博列表');

        $data = AdvertiserBusiness::getWeiboInventoryList(
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
            'num_audience',
            'original_price',
            'current_price'
        ];

        if (isset($weiboCampaignID))
        {
            $shownColumns['operations'] = [
                Table::COLUMN_OPERATIONS => [
                    new Link(
                        '添加渠道',
                        "javascript:wbtAPI.call('weibo_order/create', {campaign:'{$weiboCampaignID}', inventory:'{{this.weibo_inventory_id}}'}, function(data){ bluefinBH.showInfo('" . _APP_('Channel is successfully added.') . "', function() { location.reload(); }); });",
                        ['visible' => function($row) { return WeiboOrder::isActionAllowed(Model::OP_CREATE, $row); } ]
                    )
                ]
            ];

            $this->_view->set('campaign', $weiboCampaignID);
        }

        $table = Table::fromDbData($data, $outputColumns, WeiboInventory::WEIBO_INVENTORY_ID, $paging, $shownColumns,
            ['class' => 'table-bordered table-striped table-hover' ]
        );

        $table->showRecordNo = true;

        $operations = new \Bluefin\HTML\Container();

        if (isset($this->_requestSource))
        {
            $backButton = new Button('返回', $this->_requestSource);
            $operations->addComponent($backButton);
        }

        $this->_view->set('location', ['advertiser', isset($weiboCampaignID) ? 'campaign' : 'inventory', $this->_gateway->getActionName()]);
        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
        $this->_view->set('operations', $operations);
    }

    public function depositRecordListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();
        //获取来源
        $from = array_try_get($condition, '_from', _R('m-c-a', ['index']), true);

        $navBar = new NavBar('充值记录');

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
                    new Link(
                        '详情',
                        "#"
                    ),
                    new Link(
                        '支付',
                        "javascript:callWBTService('user_deposit_record/do_pay', '{{this.user_deposit_record_id}}');",
                        ['visible'=>function($row) { return UserDepositRecord::isActionAllowed(UserDepositRecord::TO_PAY, $row); }]
                    ),
                    new Link(
                        '取消',
                        "javascript:callWBTService('user_deposit_record/do_cancel', '{{this.user_deposit_record_id}}');",
                        ['visible'=>function($row) { return UserDepositRecord::isActionAllowed(UserDepositRecord::TO_CANCEL, $row); }]
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

    public function expenseRecordListAction()
    {
        //要求微博推账号登录
        $auth = $this->_requireAuth('weibotui');
        //获取参数
        $condition = App::getInstance()->request()->getQueryParams();
        //获取来源
        $from = array_try_get($condition, '_from', _R('m-c-a', ['index']), true);

        $navBar = new NavBar('消费记录');

        $data = UserBusiness::getUserExpenseRecordList($auth->getUniqueID(),
            $condition,
            $paging,
            $outputColumns
        );

        $shownColumns = [
            'serial_no',
            'batch_id',
            'user',
            'amount',
            'usage',
            'status'
        ];

        $table = Table::fromDbData($data, $outputColumns, UserExpenseRecord::SERIAL_NO, $paging, $shownColumns, ['class' => 'table-bordered table-striped table-hover' ]
        );

        $table->showRecordNo = true;

        $this->_view->set('location', ['advertiser', 'account', $this->_gateway->getActionName()]);
        $this->_view->set('navBar', $navBar);
        $this->_view->set('table', $table);
    }
}
