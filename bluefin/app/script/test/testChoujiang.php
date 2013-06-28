<?php
require_once '../../../lib/Bluefin/bluefin.php';
require_once '../../../webroot/raw/weixin/choujiang.php';

use Bluefin\App;

testChoujiang();

function testChoujiang()
{

    $wxID='asdjfaldjalfadsfagf';
    //echo WeixinChoujiang::responseActionCreate($wxID, "金果创新年会抽奖活动2");
    echo WeixinChoujiang::responseActionSwitch($wxID, "1");
    echo WeixinChoujiang::responseActionAdd($wxID, 'test 吕良博 maggie 徐伟');
    echo WeixinChoujiang::responseActionAdd($wxID, 'test2 郭定明 Rogers');
    echo WeixinChoujiang::responseActionAdd($wxID, 'test3');
    echo WeixinChoujiang::responseActionList($wxID, '1');
    //echo WeixinChoujiang::responseActionDelete($wxID,"崔广斌");
    echo WeixinChoujiang::responseActionLuck($wxID, '3 一等奖');

}