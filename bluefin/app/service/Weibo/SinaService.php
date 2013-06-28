<?php

use Bluefin\Service;
use Bluefin\App;
use WBT\Business\SinaWeiboBusiness;

class SinaService extends Service
{
    /* 发送微博
     * url：/api/weibo/sina/send
     *
     * @param string uid : 新浪微博用户id
     * @param string text : 发送的文本内容
     * @param int rt_weiboid : 要转发的微博id，原创微博不用设置此参数
     * @param array dingshi_time : 定时微博时间，如果非定时微博，不填此字段，或内容为空即可。
     *              ajax调用时的josn格式示例：{'year':2012, 'month':9, 'day':15, 'hour':8, 'min':2}
     */
    public  function send()
    {
        $res = array('errno' => 0);

        $text = App::getInstance()->request()->get('text');
        $uid = App::getInstance()->request()->get('uid');
        $rtWeiboID = App::getInstance()->request()->get('rt_weiboid',0);
        $arrDingshiTime = App::getInstance()->request()->get('dingshi_time');
        $imageUrl = App::getInstance()->request()->get('image_url');

        if(empty($uid))
        {
            $error = '参数错误：未指定用户';
            $res['errno'] = 1;
            $res['error'] = $error;
            log_error("[error:$error]");
            return $res;
        }


        // 检查是否有权限发
        if(!SinaWeiboBusiness::isOwnerOf($uid))
        {
            $error = '未能发布微博。原因：用户未经授权';
            $res['errno'] = 1;
            $res['error'] = $error;
            log_error("[error:$error]");
            return $res;
        }

        if(empty($arrDingshiTime))
        {
            $res =  SinaWeiboBusiness::sendWeibo($uid,$text,$imageUrl, $rtWeiboID);
            log_info("[uid:$uid][rtWeiboID:$rtWeiboID][imageUrl:$imageUrl][weibo_url:{$res['weibo_url']}][errno:{$res['errno']}][error:{$res['error']}][text:$text]");

            return $res;
        }
        else
        {

            $year = intval($arrDingshiTime['year']);
            $month = intval($arrDingshiTime['month']);
            $day = intval($arrDingshiTime['day']);
            $hour = intval($arrDingshiTime['hour']);
            $min = intval($arrDingshiTime['min']);

            $dingshiTime = mktime($hour, $min, 0, $month , $day, $year);
            $interval_error_time_s = 60; // 最多早60秒
            $now = time();
            if($now - $dingshiTime > $interval_error_time_s)
            {
                $error = '定时微博发布时间不能晚于当前时间';
                $res['errno'] = 1;
                $res['errno'] = $error;
                log_error($error);
                return $res;
            }

            return SinaWeiboBusiness::addDingshiWeibo($uid, $text, $imageUrl, $rtWeiboID, $dingshiTime);
        }
    }
}
