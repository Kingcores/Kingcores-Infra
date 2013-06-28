<?php

namespace WBT\Business;

use Common\Helper\UniqueIdentity;
use Bluefin\App;
use WBT\Model\Weibotui\OAuthToken;

class SsoBusiness
{
    const SESSION_TICKET_PREFIX = 'stk:';

    public static function issueUserTicket($userID)
    {
        $uid = UniqueIdentity::generate(32);
        $l1Cache = App::getInstance()->cache('l1');
        $l1Cache->set(self::SESSION_TICKET_PREFIX . $uid, $userID, 60);
        return $uid;
    }

    public static function getUserIdByTicket($ticket)
    {
        $l1Cache = App::getInstance()->cache('l1');
        $userID = $l1Cache->get(self::SESSION_TICKET_PREFIX . $ticket);
        $l1Cache->remove(self::SESSION_TICKET_PREFIX . $ticket);
        return $userID;
    }
}
