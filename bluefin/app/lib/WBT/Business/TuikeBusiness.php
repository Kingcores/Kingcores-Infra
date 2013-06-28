<?php

namespace WBT\Business;

use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\WeiboCampaign;
use WBT\Model\Weibotui\WeiboInventory;
use WBT\Model\Weibotui\WeiboOrder;
use WBT\Model\Weibotui\WeiboOrderStatus;

class TuikeBusiness
{
    public static function getTuikeWeiboOrderList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [WeiboOrder::STATUS]);
        $condition[WeiboOrder::TUIKE] = $userID;

        if (array_key_exists(WeiboOrder::STATUS, $condition))
        {
            if (is_array($condition[WeiboOrder::STATUS]))
            {
                unset($condition[WeiboOrder::STATUS]);
            }
            else if (!in_array($condition[WeiboOrder::STATUS], [WeiboOrderStatus::PUBLISHED, WeiboOrderStatus::ACCEPTED, WeiboOrderStatus::SUBMITTED, WeiboOrderStatus::PAID]))
            {
                unset($condition[WeiboOrder::STATUS]);
            }
        }

        if (!array_key_exists(WeiboOrder::STATUS, $condition))
        {
            $condition[WeiboOrder::STATUS] = [WeiboOrderStatus::PUBLISHED, WeiboOrderStatus::ACCEPTED, WeiboOrderStatus::SUBMITTED, WeiboOrderStatus::PAID];
        }

        $selection = ['*', 'inventory.weibo.*' => 'weibo', 'campaign.*'];

        return WeiboOrder::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            [WeiboOrder::STATUS],
            $paging,
            $outputColumns
        );
    }

    /**
     * 获取推客的渠道列表。
     *
     * @param int $userID
     * @param array $condition
     * @param array $paging
     * @param array $outputColumns
     * @return mixed
     */
    public static function getTuikeWeiboInventoryList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [WeiboInventory::TYPE, WeiboInventory::STATUS]);
        $condition[WeiboInventory::USER] = $userID;

        $selection = ['*', 'weibo.*' => 'weibo'];

        return WeiboInventory::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            null,
            $paging,
            $outputColumns
        );
    }

    public static function getInventoryWeiboIDList($userID)
    {
        return WeiboInventory::fetchColumn(WeiboInventory::WEIBO, [WeiboInventory::USER => $userID]);
    }

    public static function getWeiboList($userID, array &$paging = null, array &$outputColumns = null)
    {
        return Weibo::fetchRowsWithCount(
            '*',
            [ Weibo::USER => $userID ],
            null,
            null,
            $paging,
            $outputColumns
        );
    }
}
