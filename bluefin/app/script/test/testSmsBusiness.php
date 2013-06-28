<?php
require_once '../../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use WBT\Business\SmsBusiness;


testSendSms();

function testSendSms()
{
    $app = \Bluefin\App::getInstance();

    $msg = '测试消息:'.rand().'。来自weibotui.com';
    $mobile = 13699255409;
    $res = SmsBusiness::send($mobile,$msg);
    var_dump($res);
}
