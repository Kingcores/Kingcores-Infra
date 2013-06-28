<?php
/**
 * 每天处理ping文件
 * User: ideaone
 * Date: 13-1-4
 * Time: 下午3:53
 */

require_once '../../../../lib/Bluefin/bluefin.php';


use Bluefin\App;


$GLOBALS['fileDir'] = $argv[1];
$GLOBALS['siteName'] = $argv[2];
function get_yestoday() {
    $yestoday_time = mktime(date("H"), date("i"), 0, date("m")  , date("d")-1, date("Y"));
    return date("Y-m-d",$yestoday_time);
}

function process(){
//    $filename = "/kingcores/data/ping/pingshoudu_1_".get_yestoday().".ping";
    $filename = $GLOBALS['fileDir'].get_yestoday().".ping";
    $ping = file($filename);

    for($i=0,$j=0,$errorNum=0;$i<count($ping);$i++)
    {
        $str = substr($ping[$i],0,2);

        if("64" == $str){
            preg_match_all("/time=([0-9.]+)/",$ping[$i],$match);
            $str1[$j++] = $match[1][0];
        }
        if("Re" == $str){
            $errorNum++;
        }
    }
    sort($str1);
    //ping 总数
    $pingCount = count($str1) + $errorNum;
    //ping 成功数
    $pingSuccess = count($str1);
    //ping 失败数
    $pingError = $errorNum;
    //ping 成功率
    $pingRate = $pingSuccess / $pingCount * 100 ."%";
    //ping 最长时间
    $pingMax = $str1[count($str1) -1 ]." ms";
    //ping 最短时间
    $pingMin = $str1[0]." ms";
    //ping 平均时间
    $pingAvg = substr(array_sum($str1) / $pingCount,0,5) ." ms";

    $pingInfo =  "ping 总数：".$pingCount."<br>".
                 "ping 成功的次数：".$pingSuccess."<br>".
                 "ping 失败的次数：".$pingError."<br>".
                 "ping 成功率：".$pingRate."<br>".
                 "ping 最长时间：".$pingMax."<br>".
                 "ping 最短时间：".$pingMin."<br>".
                 "ping 平均时间：".$pingAvg;
    \WBT\Business\MailBusiness::sendMail('pub-rnd@kingcores.com','[server-ping-data] - '.get_yestoday().' - '.$GLOBALS['siteName'].' - ping statistics',$pingInfo);
}

process();

?>
