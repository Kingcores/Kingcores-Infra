<?php

namespace WBT\Business;

use WBT\Model\Weibotui\WeiboCampaign;
use WBT\Model\Weibotui\WeiboInventory;
use WBT\Model\Weibotui\WeiboOrder;

class AdvertiserBusiness
{
    /**
     * @param $userID
     * @param array $condition
     * @param array $paging
     * @param array $outputColumns
     * @return mixed
     */
    public static function getUserWeiboCampaignList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [WeiboOrder::STATUS]);
        $condition[WeiboCampaign::USER] = $userID;

        $selection = ['*'];

        return WeiboCampaign::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            ['start_time'],
            $paging,
            $outputColumns
        );
    }

    /**
     * @param $userID
     * @param $campaignID
     * @return array
     */
    public static function getUserWeiboCampaignDetail($userID, $campaignID)
    {
        $campaign = new WeiboCampaign([WeiboCampaign::WEIBO_CAMPAIGN_ID => $campaignID, WeiboCampaign::USER => $userID]);

        return $campaign->data();
    }

    public static function getUserWeiboOrderList($userID, array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [WeiboOrder::CAMPAIGN, WeiboOrder::STATUS]);
        $condition[WeiboOrder::ADVERTISER] = $userID;

        $selection = ['*', 'inventory.weibo.*' => 'weibo'];

        return WeiboOrder::fetchRowsWithCount(
            $selection,
            $condition,
            null,
            [WeiboOrder::STATUS],
            $paging,
            $outputColumns
        );
    }

    public static function getUserWeiboCampaignOrderIDs($campaignID)
    {
        return WeiboOrder::fetchColumn(WeiboOrder::INVENTORY, [WeiboOrder::CAMPAIGN => $campaignID]);
    }

    /**
     * 获取微博渠道列表。
     *
     * @param array $condition
     * @param array $paging
     * @param array $outputColumns
     * @return mixed
     */
    public static function getWeiboInventoryList(array $condition, array &$paging = null, array &$outputColumns = null)
    {
        //过滤查询条件
        $condition = array_get_all($condition, [WeiboInventory::WEIBO_INVENTORY_ID, WeiboInventory::TYPE]);
        $condition[WeiboInventory::STATUS] = \WBT\Model\Weibotui\InventoryStatus::AVAILABLE;

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

    /**
     * 创建微博订单。
     *
     * @param $campaignID
     * @param $inventoryID
     * @throws \Bluefin\Exception\InvalidRequestException
     * @throws \Exception
     */
    public static function createWeiboOrder($campaignID, $inventoryID)
    {
        $db = \Bluefin\App::getInstance()->db('weibotui');

        $db->getAdapter()->beginTransaction();

        try
        {
            $inventory = new WeiboInventory($inventoryID);

            if ($inventory->isEmpty())
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _APP_('The inventory does not exist.')
                );
            }

            if ($inventory->getStatus() != \WBT\Model\Weibotui\InventoryStatus::AVAILABLE)
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _APP_('The inventory is not available.')
                );
            }

            $order = new WeiboOrder();
            $order->setCampaign($campaignID)
                ->setInventory($inventoryID)
                ->setPrice($inventory->getCurrentPrice())
                ->insert();

            $db->getAdapter()->commit();
        }
        catch (\Exception $e)
        {
            $db->getAdapter()->rollback();

            throw $e;
        }
    }
    /**
     * 创建新的活动。
     *
     * @param $name
     * @param $type
     * @param $startTime
     * @param $endTime
     * @param $budget
     * @param $text
     */
    public static function createWeiboCampaign($name, $type, $startTime, $endTime, $budget, $text)
    {
        $weiboCampaign = new WeiboCampaign();
        $weiboCampaign->setName($name)
            ->setType($type)
            ->setStartTime($startTime)
            ->setEndTime($endTime)
            ->setBudget($budget)
            ->setText($text)
            ->insert();
    }
}
