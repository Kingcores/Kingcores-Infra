<?php
require_once '../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use WBT\Business\SinaWeiboBusiness;


try
{
    process();
}
catch (ServerErrorException $srvEx)
{
    //转到服务器错误页
    $errorCode = $srvEx->getCode();
    $message = \Bluefin\Common::getStatusCodeMessage($errorCode);

    log_error('Server Error: ' . $srvEx->getMessage() . "\n" . $srvEx->getTraceAsString());
}
catch (Exception $e)
{
    $errorCode = \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE;
    $message = \Bluefin\Common::getStatusCodeMessage($errorCode);
    log_error('Unknown Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
}


function process()
{
    $app = \Bluefin\App::getInstance();

    $endTime = time();

    // 近10分钟内的微博
    $startTime = time() - 10 * 60;

    $dingshiWeiboIDArray=  SinaWeiboBusiness::getDingshiWeiboToSendAndUpdateStatusToSending($startTime, $endTime);

    $dingshiWeiboCount= count($dingshiWeiboIDArray);
    $sentFailedCount = 0;
    foreach($dingshiWeiboIDArray as $dingshiWeiboID)
    {
        try
        {
            $res =  SinaWeiboBusiness::sendDingWeibo($dingshiWeiboID);
            if($res['errno'])
            {
                $sentFailedCount++;
            }
        }
        catch (ServerErrorException $srvEx)
        {
            //转到服务器错误页
            $errorCode = $srvEx->getCode();
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);
            log_error('Server Error: ' . $srvEx->getMessage() . "\n" . $srvEx->getTraceAsString());
        }
        catch (Exception $e)
        {
            $errorCode = \Bluefin\Common::HTTP_SERVICE_UNAVAILABLE;
            $message = \Bluefin\Common::getStatusCodeMessage($errorCode);
            log_error('Unknown Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

    }

    log_info("[CRONTAB_DINGSHI_WEIBO][dingshiWeiboCount:$dingshiWeiboCount][sentFailedCount:$sentFailedCount]");
}
