<?php
/**
 * 监控长微博转换图片,如果连续两次失败则短信报警
 * User: cuiguangbin
 * Date: 13-1-4
 */
require_once '../../../lib/Bluefin/bluefin.php';


use Bluefin\App;
use \Snoopy\Snoopy;
use \WBT\Business\SmsBusiness;


function isConvertOK(&$error)
{
    $snoopy = new Snoopy();
    $convert_url = 'http://www.changweibo.com/convert_changweibo_com.php';

    $submit_vars = array();
    $submit_vars['text'] = 'MONITOR_CONVERT';
    $submit_vars['no_ad'] = 1;

    $ret = false;
    $error = '';

    if($snoopy->submit($convert_url, $submit_vars))
    {
        $response = trim($snoopy->results);

        $response = (array)json_decode($response);
        if(isset($response['errno']))
        {
            if($response['errno'] == 0)
            {
                $ret =  true;
            }
            else
            {
                $error = 'errno != 0';
                $ret =  false;
            }
        }
        else
        {
            $error = 'unknown format';
            $ret = false;
        }
    }
    else
    {
        $error = 'snoopy submit return false';
        $ret = false;
    }

    log_info("[CHANGWEIBO_MONITOR:$ret][info:$error]");

    return $ret;
}

// 两次连续转换失败后报警
function process()
{
    $error = '';
    if(!isConvertOK($error))
    {
        if(!isConvertOK($error))
        {
            $cuiguangbinMobile = '13699255409';
            $errorInfo = "长微博转换失败，错误信息：$error";
            \WBT\Business\SmsBusiness::send($cuiguangbinMobile, $errorInfo);

            $lvliangboMobile = '18610909360';
            \WBT\Business\SmsBusiness::send($lvliangboMobile, $errorInfo);

            \WBT\Business\MailBusiness::sendMail('cuiguangbin@kingcores.com','长微博转换失败',$errorInfo);
            \WBT\Business\MailBusiness::sendMail('lvliangbo@kingcores.com','长微博转换失败',$errorInfo);
        }
    }
}

process();


