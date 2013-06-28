<?php

namespace WBT\Business;
use Snoopy\Snoopy;

class SmsBusiness
{
    public static function send($mobile, $msg)
    {
        $res = array('errno' => 0);
        $snoopy = new Snoopy();
        //todo : 部分内容写到配置文件中
        define('SMS_API_URL','http://weibotuisms.sinaapp.com/send.php');
        define('SMS_TOKEN','ffadksfads2kfal45dwiewf5kad78v23xk');

        $url = SMS_API_URL . '?token=' . SMS_TOKEN . '&mobile=' . $mobile . '&msg=' . $msg; // todo : url zhuanma
        // echo $url;
        // return json_decode(file_get_contents($url));

        $submit_vars = array();
        $submit_vars['mobile'] = $mobile;
        $submit_vars['msg'] = $msg;
        $submit_vars['token'] = SMS_TOKEN;

        if($snoopy->submit(SMS_API_URL, $submit_vars))
        {
            $response = trim($snoopy->results);

            $response = (array)json_decode($response);
            if(isset($response['errno']))
            {
                $res = $response;
            }else
            {
                $res['errno'] = 1;
                $res['error'] = 'sms api response format error.';
            }
        }else
        {
            $res['errno'] = 1;
            $res['error'] = 'sms api  error.';
        }
        log_info("[SMS_SEND][errno:{$res['errno']}][mobile:$mobile][msg:$msg]");

        return $res;
    }
}