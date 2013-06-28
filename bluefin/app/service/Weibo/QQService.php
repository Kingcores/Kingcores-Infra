<?php

use Bluefin\App;
use Bluefin\Service;
use WBT\Business\QQWeiboBusiness;

class QQService extends Service
{
    /* 发送微博
     * url：/api/weibo/qq/send
     * @param
     */
    public function send()
    {
        $res = array('errno' => 0);
        $mobile = $_REQUEST['mobile'];
        $msg = $_REQUEST['msg'];
        // todo : check format
        if(empty($mobile) || empty($msg))
        {
            $res['errno'] = 1;
            $res['error'] = 'format error';
            return $res;
        }
    }

}
