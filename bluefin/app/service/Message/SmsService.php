<?php

use Bluefin\Service;
use Bluefin\App;
use WBT\Business\SmsBusiness;

class SmsService extends Service
{
    /*
    // api/message/sms/send
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

        $sms  = new SmsBusiness();
        return $sms->send($mobile, $msg);
    }
    */

    // api/message/sms/send_auth_code
    public function sendAuthCode()
    {
        $res = array('errno' => 0);

        $mobile = trim(App::getInstance()->request()->get('mobile'));

        if (empty($mobile))
        {
            $error = '手机号为空';
            $res['errno'] = 1;
            $res['error'] = $error;

            return $res;
        }

        $sms  = new SmsBusiness();

        // 随机生成一个四位数
        $msg = '';
        for($i = 0; $i < 4; $i++)
        {
            $msg .= rand(0,9);
        }

        // 将msg保存到session 中，以验证
        App::getInstance()->session()->set('sms_auth_code', $msg);

        // 增加可读性信息
        $msg = '微博推(www.weibotui.com)手机验证码：'.$msg;

        // 发送短信
        return $sms->send($mobile, $msg);
    }

}
