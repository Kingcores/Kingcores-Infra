<?php

require_once '../../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use \WBT\Model\Weibotui\WeixinToMail;
use \WBT\Business\MailBusiness;
use Upyun\WeibotuiUpyun;


//define your token
define("TOKEN", "skljusndflasdkjf");


$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
    const SIMPLE_HELP = "请回复您的email地址,如me@a.cn。\n\n成功绑定email后再向本微信帐号发送消息，即可将消息内容转发到绑定的email。目前支持的消息类型有文本、图片和地理位置。另外，还可以通过'to'指令给他人发邮件，请输入'h'或'help'或'?'查看帮助信息。";

    public function valid()
    {
        $app = \Bluefin\App::getInstance();

        //$echoStr = $_GET["echostr"];

        log_debug('request:', $_REQUEST);

        //valid signature , option
        if(!$this->checkSignature()){
            log_error("checkSignature() failed.");
            // echo $echoStr;
            exit;
        }

        // 第一次在公众平台填写接口时的验证
        if(isset($_GET["echostr"]))
        {
            echo $_GET["echostr"];
            exit;
        }
    }

    // 判断一个字符串是否是email
    public static function isEmail($str)
    {
        $matches = null;
        $PATTERN_EMAIL = '/^[a-z0-9]([a-z0-9]*[-_\.]?[a-z0-9]+)*@[a-z0-9]([a-z0-9]*[-_\.]?[a-z0-9]+)*$/i';
        return preg_match($PATTERN_EMAIL,$str,$matches);
    }

    private static function getHintInfoByEmail($email)
    {
        if(strstr($email,'@m.evernote.com')) return '';
        if(strstr($email,'@m.yinxiang.com')) return '';
        if(strstr($email,'@mywiz.cn')) return '';
        if(strstr($email,'@doitim.in')) return '';

        return '如未收到邮件，请检检查垃圾邮件，如果在垃圾邮件中，请将发件人加为联系人。';
    }

    // 返回提示语
    private static function sendMail($weixinUID,$subject, $content)
    {
        $content .= '<br><br>----<br>本邮件由微邮箱（微信ID：weixintomail）发送';
        $wm = new WeixinToMail([\WBT\Model\Weibotui\WeixinToMail::WEIXIN_UID => $weixinUID]);
        if ($wm->isEmpty())
        {
            log_error("could not find record from db.[weixinUID:$weixinUID]");
            return '抱歉，微邮箱程序内部错误。';
        }

        $email = $wm->getEmail();
        $reply = $email; //$wm->getFrom(); // 邮件回复地址
        $to = $wm->getTo();

        if(empty($to))
        {
            $isOK =  \WBT\Business\MailBusiness::sendMail($email, $subject, $content);
            if($isOK)
            {
                $sendCount = $wm->getSendCount() + 1;
                $wm->setSendCount($sendCount);
                $wm->save();
                $str = '以上内容已发送给送到'.$email.'。';
                if($sendCount == 1)
                {
                    $str .= '如果被当作垃圾邮件，请将发件人设置为联系人。';
                }
                return $str;
            }
            else
            {
                return '抱歉，邮件发送失败。请稍候重试';
            }
        }else
        {
            // 清空收件人信息
            $wm->setTo('');

            $isOK =  \WBT\Business\MailBusiness::sendMailWithReply($to, $reply, $subject, $content );
            if($isOK)
            {
                $sendCount = $wm->getSendCount() + 1;
                $wm->setSendCount($sendCount);
                $wm->save();
                $str = '以上内容已发送给送到'.$to.'。';
                if($sendCount == 1)
                {
                    $str .= '如果被当作垃圾邮件，请将发件人设置为联系人。';
                }
                return $str;
            }
            else
            {
                $wm->save();
                return '抱歉，邮件发送失败。请稍候重试';
            }
        }
    }


    public static function getAnswerByImage($weixinUID,$imageUrl)
    {
        $weixinToMail = new WeixinToMail([\WBT\Model\Weibotui\WeixinToMail::WEIXIN_UID => $weixinUID]);
        if ($weixinToMail->isEmpty())
        {
            return  self::SIMPLE_HELP;
        }

        $email = $weixinToMail->getEMail();
        $subject = '['.date('Y-m-d H:i').']来自微信的图片';

        log_info("[imageUrl:$imageUrl]");

        // 如果是印象笔记，需要中转图片，因为QQ空间不让evernote.com抓取。。。
        if(strstr($email,'@m.evernote.com'))
        {
            // 更新成友拍云的URL
            $imageUrl = WeibotuiUpyun::uploadImage($imageUrl);
        }

        log_info("[imageUrl:$imageUrl]");
        $content = "<span>$subject</span><br><img src=\"$imageUrl\"></img>";

        return self::sendMail($weixinUID,$subject,$content);
    }


    // x: 纬度， y:经度
    public static function getAnswerByLocation($weixinUID,$label, $scale, $x, $y)
    {
        $weixinToMail = new WeixinToMail([\WBT\Model\Weibotui\WeixinToMail::WEIXIN_UID => $weixinUID]);
        if ($weixinToMail->isEmpty())
        {
            return  self::SIMPLE_HELP;
        }

        $email = $weixinToMail->getEMail();
        $subject = '['.date('Y-m-d H:i')."]来自微信的位置： ".$label;

        $mapImageUrl = "http://api.map.baidu.com/staticimage?center=$y,$x&width=600&height=600&zoom=$scale&markers=$y,$x";

        $content = '<span>'.$subject."</span><br><img src=\"$mapImageUrl\"></img>";


        return self::sendMail($weixinUID,$subject,$content);

    }

    public static function getAnswerByText($weixinUID,$text)
    {
        $answer = '';

        $help =
            "1) 回复'h'或'help'或'?'查看本帮助信息。\n\n".
                "2) 未绑定email时，请直接回复您的email地址。\n\n".
                "3) 成功绑定email后再微邮箱发送消息，即可将内容发送到绑定的email。目前支持的消息类型有：文本、图片和地理位置。如果未收到邮件，请检查一下垃圾邮件。\n\n".
                "4) 更换绑定的email:输入 rebind email地址，如：rebind me@b.cn\n\n".
                "5) 给他人发email:输入 to email地址，如：to peter@b.cn 。然后在下一条消息输入要发送的内容。\n\n".
                "6) 快速转发消息技巧：在微信中长按要发送的内容，然后选择转发，再选择‘微邮箱’即可快速把内容发送给微邮箱。\n\n".
                "7) 欢迎反馈问题或建议，回复“feedback 反馈内容”或“fb 反馈内容”即可。\n\n".
                "8) 如果您觉得微邮箱还不错，欢迎推荐给您的好友。 连续两次点击右上角按钮，选择'推荐给朋友' 。\n\n".
                "";

        if ($text == 'Hello2BizUser')
        {
            return self::SIMPLE_HELP;
        }

        if ((strtolower($text) == 'h') or (strtolower($text) == 'help')  or ($text == '?') or ($text == '？'))
        {
            return $help;
        }

        // feedback
        $feedbackKey = 'feedback ';
        $feedbackKeyLen = strlen($feedbackKey);
        if (strtolower(substr($text, 0, $feedbackKeyLen)) == $feedbackKey)
        {
            return '感谢您的反馈。';
        }

        $feedbackKey = 'fb ';
        $feedbackKeyLen = strlen($feedbackKey);
        if (strtolower(substr($text, 0, $feedbackKeyLen)) == $feedbackKey)
        {
            return '感谢您的反馈。';
        }


        $weixinToMail = new WeixinToMail([\WBT\Model\Weibotui\WeixinToMail::WEIXIN_UID => $weixinUID]);

        $just_bind_email_str = trim($text);

        // 未绑定邮箱时，直接输入邮箱即可绑定
        if ($weixinToMail->isEmpty())
        {
            // 判断是否是绑定邮箱
            $email = trim($just_bind_email_str);
            if(!self::isEmail($email))
            {
                return '您还未绑定个人邮箱，请直接输入您的email，如：me@a.cn ';
            }

            if(strstr($email,'@mywiz.com'))
            {
                return '您输入的为知笔记邮件有误，邮箱后缀应该是@mywiz.cn';
            }


            $weixinToMail->setEMail($email);
            //$weixinToMail->setFrom($email); 不再使用from 字段
            $weixinToMail->setWeixinUID($weixinUID);
            $weixinToMail->save();

            return 'email绑定成功，您绑定的email是：'.$email .'。再向本微信帐号发送消息，即可将消息内容发送到'.$email.'。';
        }

        // 重新绑定
        $rebind_key ='rebind ';
        $rebind_len = strlen($rebind_key);
        if (strtolower(substr($just_bind_email_str, 0, $rebind_len)) == $rebind_key)
        {
            $email = substr($just_bind_email_str, $rebind_len);
            $email = trim($email);

            if(!self::isEmail($email))
            {
                return '您输入的email格式有误，请检查。';
            }

            if(strstr($email,'@mywiz.com'))
            {
                return '您输入的为知笔记邮件有误，应该是@mywiz.cn。';
            }

            $weixinToMail->setEMail($email);
            $weixinToMail->setWeixinUID($weixinUID);
            $weixinToMail->save();

            return 'email更新成功，您绑定的新email地址是：'.$email .'。';
        }

        // 发给其它人
        $to_key = 'to ';
        $to_len = strlen($to_key);
        if (strtolower(substr($text, 0, $to_len)) == $to_key)
        {
            $to = substr($text, $to_len);
            $to = trim($to);

            //$from = $weixinToMail->getFrom();
            // to 后面的内容的确是邮件
            if(self::isEmail($to))
            {
                $weixinToMail->setTo($to);
                $weixinToMail->save();

                return "您接下来输入的内容会发送给".$to."。";
            }
        }

        // 发给其它人
        $to_key = 'to:';
        $to_len = strlen($to_key);
        if (strtolower(substr($text, 0, $to_len)) == $to_key)
        {
            $to = substr($text, $to_len);
            $to = trim($to);

            //$from = $weixinToMail->getFrom();
            // to 后面的内容的确是邮件
            if(self::isEmail($to))
            {
                $weixinToMail->setTo($to);
                $weixinToMail->save();


                return "您接下来输入的内容会发送给".$to."。";
            }
        }
        // 发给其它人
        $to_key = 'to：';
        $to_len = strlen($to_key);
        if (strtolower(substr($text, 0, $to_len)) == $to_key)
        {
            $to = substr($text, $to_len);
            $to = trim($to);

            //$from = $weixinToMail->getFrom();
            // to 后面的内容的确是邮件
            if(self::isEmail($to))
            {
                $weixinToMail->setTo($to);
                $weixinToMail->save();

                return "您接下来输入的内容会发送给".$to."。";
            }
        }

        /*
        // 更新回复地址
        $from_key = 'from ';
        $from_len = strlen($from_key);
        if (strtolower(substr($text, 0, $from_len)) == $from_key)
        {
            $from = substr($text,$from_len);
            $from = trim($from);

            // from 后面的内容的确是邮件
            if(self::isEmail($from))
            {
                $weixinToMail->setFrom($from);
                $weixinToMail->save();

                return '邮件回复地址已更新为' . $from .'。';
            }

        }
         */

        $email = $weixinToMail->getEMail();
        $subject = substr_unicode($text,0,40);

        $text = nl2br($text);
        return self::sendMail($weixinUID,$subject,$text);
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        log_info("ORG_POST_STR:$postStr");
        if (!empty($postStr)){

            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $receiveMsgType = $postObj->MsgType;
            $contentStr ='';
            if($receiveMsgType == 'text')
            {
                $keyword = trim($postObj->Content);


                // test weixin api
                if($keyword == 'TESTIMG')
                {
                    $time = time();
                    $msgType = "image";
                    $contentStr = 'http://weixintomail.weibotui.com/images/logo.png';
                    $textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><PicUrl><![CDATA[%s]]></PicUrl><FuncFlag>0</FuncFlag></xml>";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                    return;
                }

                if (!empty($keyword))
                {
                    $contentStr = self::getAnswerByText($fromUsername,$keyword);

                } else {
                    log_error("error：no user data.");
                }
            }
            elseif($receiveMsgType == 'image')
            {
                $imageUrl = $postObj->PicUrl;
                $contentStr = self::getAnswerByImage($fromUsername, $imageUrl);
            }
            elseif($receiveMsgType == 'location')
            {
                $label = $postObj->Label;
                $scale = $postObj->Scale;
                $x = $postObj->Location_X;
                $y = $postObj->Location_Y;
                $contentStr = self::getAnswerByLocation($fromUsername, $label, $scale, $x, $y);
            }
            elseif($receiveMsgType == 'voice')
            {
                $contentStr = '抱歉，目前因腾讯API限制，还不支持语音。';
            }
            else
            {
                $contentStr = '抱歉，目前还不支持您刚才发的消息格式。';
                log_error("error receiveMsgType:$receiveMsgType");
            }

            log_info("[user:$fromUsername][receive:$keyword][send:$contentStr]");
            if(!empty($contentStr))
            {
                $time = time();
                $msgType = "text";
                $textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content><FuncFlag>0</FuncFlag></xml>";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }


        }else
        {
            echo "";
            log_error('empty post data.');
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}

?>
