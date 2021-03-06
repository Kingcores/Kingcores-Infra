<?php
//Don't edit this file which is generated by Bluefin Lance.
namespace WBT\API\Weibotui;

use Bluefin\App;
use Bluefin\Common;
use Bluefin\Service;
use Bluefin\Data\Model;
use Bluefin\Data\Database;

use WBT\Model\Weibotui\WeiboCampaignStatus;
use WBT\Model\Weibotui\WeiboCampaign;

class WeiboCampaignAPI extends Service
{
    public function create()
    {
        $aclStatus = WeiboCampaign::checkActionPermission(Model::OP_CREATE);
        if ($aclStatus !== Model::ACL_ACCEPTED)
        {
            throw new \Bluefin\Exception\RequestException(null, $aclStatus);
        }

        $weiboCampaign = new WeiboCampaign();
        $weiboCampaign->reset($this->_app->request()->getPostParams());
        return $weiboCampaign->insert();
    }

    public function get(array $condition, array &$outputColumns = null, array &$paging = null)
    {
        $aclStatus = WeiboCampaign::checkActionPermission(Model::OP_GET);

        if ($aclStatus !== Model::ACL_ACCEPTED)
        {
            throw new \Bluefin\Exception\RequestException(null, $aclStatus);
        }

        return WeiboCampaign::fetchRowsWithCount(
            $outputColumns,
            $condition,
            null,
            $orderBy,
            $paging,
            $outputColumns
        );
    }


    public function doPublish($weiboCampaignID)
    {
        WeiboCampaign::doPublish($weiboCampaignID, $this->_app->request()->getPostParams());

        return 1;
    }

    public function doCancel($weiboCampaignID)
    {
        WeiboCampaign::doCancel($weiboCampaignID, $this->_app->request()->getPostParams());

        return 1;
    }

    public function doClose($weiboCampaignID)
    {
        WeiboCampaign::doClose($weiboCampaignID, $this->_app->request()->getPostParams());

        return 1;
    }
}
?>