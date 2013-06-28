<?php
//Don't edit this file which is generated by Bluefin Lance.
namespace WBT\Model;

use Bluefin\Data\Database;

class WeibotuiDatabase extends Database
{
    public function __construct(array $config)
    {
        parent::__construct(
            'WBT',
            'weibotui',
            array(
                'oauth_token' => '\\WBT\\Model\\Weibotui\\OAuthToken',
                'oauth_client' => '\\WBT\\Model\\Weibotui\\OAuthClient',
                'user' => '\\WBT\\Model\\Weibotui\\User',
                'country' => '\\WBT\\Model\\Weibotui\\Country',
                'province' => '\\WBT\\Model\\Weibotui\\Province',
                'city' => '\\WBT\\Model\\Weibotui\\City',
                'district' => '\\WBT\\Model\\Weibotui\\District',
                'address' => '\\WBT\\Model\\Weibotui\\Address',
                'personal_profile' => '\\WBT\\Model\\Weibotui\\PersonalProfile',
                'oauth_code' => '\\WBT\\Model\\Weibotui\\OAuthCode',
                'admin' => '\\WBT\\Model\\Weibotui\\Admin',
                'tuike' => '\\WBT\\Model\\Weibotui\\Tuike',
                'user_with_role' => '\\WBT\\Model\\Weibotui\\UserWithRole',
                'admin_with_role' => '\\WBT\\Model\\Weibotui\\AdminWithRole',
                'admin_role' => '\\WBT\\Model\\Weibotui\\AdminRole',
                'user_login_record' => '\\WBT\\Model\\Weibotui\\UserLoginRecord',
                'admin_login_record' => '\\WBT\\Model\\Weibotui\\AdminLoginRecord',
                'weibo' => '\\WBT\\Model\\Weibotui\\Weibo',
                'weibo_token' => '\\WBT\\Model\\Weibotui\\WeiboToken',
                'weibo_topic' => '\\WBT\\Model\\Weibotui\\WeiboTopic',
                'topic_category' => '\\WBT\\Model\\Weibotui\\TopicCategory',
                'weibo_login_record' => '\\WBT\\Model\\Weibotui\\WeiboLoginRecord',
                'corporate' => '\\WBT\\Model\\Weibotui\\Corporate',
                'staff_in_corporate' => '\\WBT\\Model\\Weibotui\\StaffInCorporate',
                'user_asset' => '\\WBT\\Model\\Weibotui\\UserAsset',
                'user_deposit_record' => '\\WBT\\Model\\Weibotui\\UserDepositRecord',
                'income' => '\\WBT\\Model\\Weibotui\\Income',
                'invoice' => '\\WBT\\Model\\Weibotui\\Invoice',
                'user_income_record' => '\\WBT\\Model\\Weibotui\\UserIncomeRecord',
                'service_income_record' => '\\WBT\\Model\\Weibotui\\ServiceIncomeRecord',
                'user_expense_record' => '\\WBT\\Model\\Weibotui\\UserExpenseRecord',
                'payout' => '\\WBT\\Model\\Weibotui\\Payout',
                'weibo_order' => '\\WBT\\Model\\Weibotui\\WeiboOrder',
                'weibo_campaign' => '\\WBT\\Model\\Weibotui\\WeiboCampaign',
                'weibo_inventory' => '\\WBT\\Model\\Weibotui\\WeiboInventory',
                'sina_dingshi_weibo' => '\\WBT\\Model\\Weibotui\\SinaDingshiWeibo',
                'qq_dingshi_weibo' => '\\WBT\\Model\\Weibotui\\QQDingshiWeibo',
                'task_queue' => '\\WBT\\Model\\Weibotui\\TaskQueue',
                'system_property' => '\\WBT\\Model\\Weibotui\\SystemProperty',
            ),
            new \Bluefin\Data\Db\MySQL($config)
        );
    }
}