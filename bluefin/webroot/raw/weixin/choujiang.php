<?php

require_once '../../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use \WBT\Model\Weibotui\Choujiang;
use \WBT\Model\Weibotui\ChoujiangResult;
use \WBT\Model\Weibotui\ChoujiangCurrentData;
use \WBT\Model\Weibotui\ChoujiangUser;

use \WBT\Business\MailBusiness;


//define your token
define("TOKEN", "skljusndflasdkjf");

WeixinChoujiang::valid();
WeixinChoujiang::responseMsg();

class WeixinChoujiang
{
    public static function valid()
    {
        $app = \Bluefin\App::getInstance();

        //$echoStr = $_GET["echostr"];

        log_debug('request:', $_REQUEST);

        //valid signature , option
        if(!self::checkSignature()){
            log_error("checkSignature() failed.");
            exit;
        }

        // 第一次在公众平台填写接口时的验证
        if(isset($_GET["echostr"]))
        {
            echo $_GET["echostr"];
            exit;
        }
    }


    public static function getAnswerByText($weixinUID,$text)
    {
        $help =
                "1) 创建抽奖活动请输入:create + 抽奖描述，如:create 金果创新年会抽奖活动 \n\n".
                "2) 添加参与抽奖者请输入:add + 参与抽奖者姓名，多个抽奖者之间用空格分割，如:add 小明 小虎\n\n".
                "3) 删除参与抽奖者请输入:delete + 参与抽奖者姓名，如:delete 小虎\n\n".
                "4) 抽看参与抽奖的名单请输入：L + 抽奖活动ID，如:L 1。\n\n".
                "5) 抽奖请输入:$ + 抽奖人数 + 抽奖描述，如: $ 3 二等奖。\n\n".
                "6) 切换抽奖活动请输入：switch + 抽奖活动ID，仅能切换到自己创建的抽奖活动。 如：switch 1002\n\n".
                "7) 欢迎反馈问题或建议，回复“feedback 反馈内容”或“fb 反馈内容”即可。\n\n".
                "8) 欢迎推荐抽奖工具给您的好友。 连续两次点击右上角按钮，选择'推荐给朋友'即可 。\n\n".
                "9) 回复'h'或'help'或'?'查看本帮助信息。\n\n".
                "";

        if ($text == 'Hello2BizUser')
        {
            return "感谢您关注'抽奖工具'。\n\n".$help;
        }

        if ((strtolower($text) == 'h') or (strtolower($text) == 'help')  or ($text == '?') or ($text == '？'))
        {
            return $help;
        }

       //if(self::hasSpecialChar($text))
       // {
       //     return '输入内容不能包含字符$';
       // }

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


        $key = 'create ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionCreate($weixinUID,$content);
        }

        $key = 'add ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionAdd($weixinUID,$content);
        }

        $key = 'delete ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionDelete($weixinUID,$content);
        }

        $key = 'luck ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionLuck($weixinUID,$content);
        }

        $key = '￥ ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionLuck($weixinUID,$content);
        }

        $key = '$ ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionLuck($weixinUID,$content);
        }


        $key = 'switch ';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionSwitch($weixinUID,$content);
        }

        $key = 'list';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionList($weixinUID,$content);
        }

        $key = 'L';
        $key_len = strlen($key);
        if(strncasecmp($key,$text, $key_len) == 0)
        {
            $content = substr($text,$key_len);
            return self::responseActionList($weixinUID,$content);
        }

        return "未知指令。请输入h或help查看帮助信息。";
    }

    public static function responseActionCreate($wxID,$content)
    {
        if(strlen(trim($content)) == 0)
        {
            return '请输入抽奖描述';
        }

        $cj = new Choujiang();
        $cj->setIntroduce($content)
           ->setWxID($wxID)
           ->insert();

        $id = $cj->getChoujiangID();

        $curData = new ChoujiangCurrentData();

        $curData->setChoujiangID($id)
                ->setWxID($wxID)
                ->insert(true);

        return "抽奖活动创建成功，编号为：".$id;
    }

    public static function getCurrentChouJiangID($wxID)
    {
        $curData = new ChoujiangCurrentData([ChoujiangCurrentData::WX_ID =>$wxID ]);
        if($curData->isEmpty())
        {
            return 0;
        }

       return $curData->getChoujiangID();
    }

    public static function responseActionAdd($wxID,$content)
    {
        $content = trim($content);
        if(strlen($content) == 0)
        {
            return '用户名不能为空';
        }

        $id = self::getCurrentChouJiangID($wxID);
        if(empty($id))
        {
            return '您还未创建抽奖活动。 请输入h或help查看帮助。';
        }

        $addUsers = '';
        $lines =  explode("\n",$content);
        foreach($lines as $line)
        {
            $users = explode(' ',$line);
            foreach($users as $user)
            {
                $cjUser = new ChoujiangUser([ChoujiangUser::CHOUJIANG_ID => $id, ChoujiangUser::USER_NAME => $user]);
                if(!$cjUser->isEmpty())
                {
                    continue;
                }

                $cjUser->setChoujiangID($id)
                    ->setUserName($user)
                    ->setOwnerWxID($wxID)
                    ->insert();

                $addUsers .= $user;
                $addUsers .= ' ';
            }
        }

        return "用户添加成功。可以通过 list 指令查看抽奖名单。";
    }

    public static function responseActionDelete($wxID,$userName)
    {
        $userName = trim($userName);
        if(strlen($userName) == 0)
        {
            return '用户名不能为空';
        }

        $id = self::getCurrentChouJiangID($wxID);
        if(empty($id))
        {
            return '您还未创建抽奖活动。 请输入h或help查看帮助。';
        }

        $cjUser = new ChoujiangUser([ChoujiangUser::CHOUJIANG_ID => $id, ChoujiangUser::USER_NAME => $userName]);
        if($cjUser->isEmpty())
        {
             return "未找到用户 $userName";
        }

        $cjUser->delete();

        return "用户删除成功。可以通过 list 指令查看抽奖名单。";
    }

    public static function responseActionLuck($wxID,$content)
    {
        $id = self::getCurrentChouJiangID($wxID);
        if(empty($id))
        {
            return '您还未创建抽奖活动。 请输入h或help查看帮助。';
        }

        $content = trim($content);
        if(strlen($content) == 0)
        {
            return '输入格式错误。抽奖示例，抽奖3个二等奖输入内容：luck 3 二等奖';
        }

        $arr = explode(' ',$content);
        $luck_count = intval($arr[0]);
        $luck_desc = '';
        if(isset($arr[1]))
        {
            $luck_desc = $arr[1];
        }

        $users = ChoujiangUser::fetchColumn(ChoujiangUser::USER_NAME, [ChoujiangUser::CHOUJIANG_ID => $id]);
        $user_count = count($users);
        if($luck_count > $user_count)
        {
            return "抽奖数（".$luck_count."）大于参与抽奖的总人数（".$user_count."）";
        }

        // 抽奖
        return $luck_desc .'中奖名单为：' . self::getRandUser($users, $luck_count);

    }

    public static function getRandUser($users, $count)
    {
        $result = '';
        for($i = 0; $i < $count; $i++)
        {
            $ele_count = count($users);
            if($ele_count == 0)
            {
                break;
            }
            $r = rand(0, $ele_count -1);

            $result .= $users[$r];
            $result .= ' ';
            array_splice($users, $r,1);
        }

        return $result;
    }

    public static function responseActionSwitch($wxID,$content)
    {
        $content = trim($content);
        if(strlen($content) == 0)
        {
            return '抽奖活动ID不能为空';
        }

        $id = intval($content);
        if(empty($id))
        {
            return '抽奖活动ID格式错误。示例：switch 1001';
        }

        $cj = new Choujiang($id);

        if($cj->isEmpty() || ($cj->getWxID() != $wxID))
        {
            return "你未增创建活动ID为".$id."的抽奖活动。";
        }

        $curData = new ChoujiangCurrentData();
        $curData->setWxID($wxID)
            ->setChoujiangID($id)
            ->insert(true);

        return '已成功切换到抽奖活动'.$id;
    }

    public static function responseActionList($wxID,$content)
    {
        $id = 0;
        $content = trim($content);
        if(strlen($content) == 0)
        {
            $id = self::getCurrentChouJiangID($wxID);
        }
        else
        {
            $id = intval($content);
        }

        if(empty($id))
        {
            return '抽奖活动ID错误。请回复h或help查看帮助信息。';
        }

        $users = ChoujiangUser::fetchColumn(ChoujiangUser::USER_NAME, [ChoujiangUser::CHOUJIANG_ID => $id]);
        $result = '';
        foreach($users as $user)
        {
            $result .= $user;
            $result .= ' ';
        }

        $cj = new Choujiang($id);
        $introduce = '';
        if(!$cj->isEmpty())
        {
            $introduce = $cj->getIntroduce();
        }

        return "抽奖活动(ID:" . $id . ")".$introduce. "参与抽奖名单为：".$result;
    }


    public static function hasSpecialChar($text)
    {
        return strstr($text,'$');
    }

    public static  function responseMsg()
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
            $keyword = '';
            if($receiveMsgType == 'text')
            {
                $keyword = trim($postObj->Content);


                if (!empty($keyword))
                {
                    $contentStr = self::getAnswerByText($fromUsername,$keyword);

                } else
                {
                    log_error("error：no user data.");
                }
            }
            else
            {
                $contentStr = '请输入文本信息。';
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

    public static  function checkSignature()
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
