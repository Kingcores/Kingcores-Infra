<?php
/**
 * User: cuiguangbin@kingcores.com
 * Date: 13-1-21.
 */

require_once '../../../lib/Bluefin/bluefin.php';


include_once 'SimpleHTMLDom/simple_html_dom.php';


// 获取一个二级分类下的所有帐号
// $url = http://5sns.com/index.php/show/gonghao/id/16/'
function getMpAccountPageList($url)
{
    $list = [];
    // 新建一个Dom实例
    $html = new simple_html_dom();

    // 从url中加载
    $html->load_file($url);

    $nodes = $html->find('div[class=w_list]', 0);

    foreach ($nodes->find('a') as $element)
    {
        $list[] = 'http://5sns.com' . $element->href;
    }
    return $list;
}

// $url ： 公众帐号具体页面
function getOneMpAccountInfo($url)
{
    $info = [];
    $html = new simple_html_dom();
    $html->load_file($url);

    // Find all links
    $table1 = $html->find('table', 0);

    $table2 = $table1->find('table',0);

    $tds =  $table2->find('td');
/*

[line:1]公众号名称：
[line:2]<span style="font-weight:bold;">互联网资讯</span> <img src="/static/skin/icon.jpg" border="0" align="texttop" />
[line:3]微信号：
[line:4]SOSEMS
[line:5]公号QQ：
[line:6]168883358
[line:7]分类：
[line:8]新闻
[line:9]地区：
[line:10]福建 厦门
[line:11]微信用户信息：
[line:12]前沿资讯，多方位互联网、IT热点，全面剖析电脑、手机、网络资讯。
[line:13]二维码：
[line:14]<img src="/static/upload/c99644b126d5023f29942821fcfbdcab.jpg" border="0" />
[line:15]&nbsp;
[line:16]微信扫描二维码可以立即关注。

    */
    $lineNo = 0;
    foreach($tds as $td)
    {
        $lineNo++;
        if($lineNo ==2)
        {
            $info['name'] = $td->find('span',0)->innertext;
        }

        if($lineNo == 4)
        {
            $info['wxid']= $td->innertext;
        }

        if($lineNo == 6)
        {
            $info['qq']= $td->innertext;
        }

        if($lineNo == 8)
        {
            $info['class'] =  $td->innertext;
        }
        if($lineNo == 12)
        {
            $info['introduce'] =  $td->innertext;
        }

        if($lineNo == 14)
        {
            $info['qrcode_url'] = 'http://5sns.com/' . $td->find('img',0)->src;
        }
    }

    return $info;
}

//
//$url = 'http://5sns.com/index.php/show/detail/id/2/';
//$url = 'http://5sns.com/index.php/show/detail/id/21/';
//$res =  getOneMpAccountInfo($url);

//echo json_encode($res);


// 获取要抓取的二级目录列表
function getSecondLevelDirectory()
{
    $list = [];
    // 新建一个Dom实例
    $html = new simple_html_dom();

    // 从url中加载
    $html->load_file('http://5sns.com/');

    $nodes = $html->find('div[id=c]', 0);

    $count = 0;
    foreach ($nodes->find('a') as $element)
    {
        $count++;
        $list[] = 'http://5sns.com' . $element->href;

        // 之后的是热门推荐，不再抓取
        if($count == 51)
        {
            break;
        }
    }
    return $list;
}


function parseAll()
{
    $secondDirList = getSecondLevelDirectory();
    foreach ($secondDirList as $dirUrl)
    {
        $accountUrlList =  getMpAccountPageList($dirUrl);
        foreach($accountUrlList as $accountUrl)
        {
            $info =  getOneMpAccountInfo($accountUrl);
            echo json_encode($info) . "\n";
        }
    }
}

function getQRCodeUrl()
{
    $file = fopen('/root/5sns.txt','r');
    if ($file)
    {
        $count = 0;
        while (!feof($file))
        {
            ++$count;
            $buffer = fgets($file,10240);
            $info = json_decode($buffer,true);
            echo $info['qrcode_url'] . "\n";

            //if($count > 5) break;
        }
    }
    fclose($file);
}

function shortenUrl($long_url)
{
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,"http://dwz.cn/create.php");
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $data=array('url'=>$long_url);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
    $strRes=curl_exec($ch);
    curl_close($ch);
    $arrResponse=json_decode($strRes,true);
    if($arrResponse['status'] != 0)
    {
        $error =  $arrResponse['err_msg'];
        log_warning($error. "[long_url:$long_url]");
        return null;
    }

    return $arrResponse['tinyurl'];
}

function shortenAllURL()
{
    $file = fopen('/root/filename_url.txt','r');
    if ($file)
    {
        $count = 0;
        while (!feof($file))
        {
            ++$count;
            $buffer = fgets($file,10240);

            $buffer=str_replace("\n","",$buffer);
            $str = $buffer;
            list($id, $url) = explode("`", $str);

            $shortUrl = shortenUrl($url);
            $info = ['id'=>$id, 'url'=> $url, 'short_url' => $shortUrl];
            echo json_encode($info) . "\n";

            if($count % 20 == 0)
            {
                sleep(1);
            }
        }
    }
    fclose($file);
}

// url = "http://5sns.com/static/upload/c99644b126d5023f29942821fcfbdcab.jpg"

function getImageName($imgUrl)
{
    $res = null;
    if(preg_match('/[a-z0-9]+\.[a-z]+$/', $imgUrl, $matches))
    {
        if(preg_match('/[a-z0-9]+/',$matches[0],$m))
        {
            $res = $m[0];
        }
    }
    return $res;
}

function getMpAccountURLFromFile()
{
    $urls = [];
    $file = fopen('/root/5sns_short_url.txt','r');
    if ($file)
    {
        $count = 0;
        while (!feof($file))
        {
            ++$count;
            $buffer = fgets($file,10240);
            $info = json_decode($buffer,true);

            $id = $info['id'];
            $urls[$id] = $info;
        }
    }

    fclose($file);

    return $urls;
}


function mergeResult()
{
    $urls =  getMpAccountURLFromFile();

    $file = fopen('/root/5sns.txt','r');
    if ($file)
    {
        $count = 0;
        while (!feof($file))
        {
            ++$count;
            $buffer = fgets($file,10240);
            $info = json_decode($buffer,true);

            $qrcode_url  = $info['qrcode_url'];

            $fileName = getImageName($qrcode_url);

            $weixin_url = null;
            $weixin_short_url = null;
            if(isset($urls[$fileName]))
            {
                $weixin_url = $urls[$fileName]['url'];
                $weixin_short_url = $urls[$fileName]['short_url'];
            }

            $info['weixin_url'] = $weixin_url;
            $info['weixin_short_url'] = $weixin_short_url;
            echo json_encode($info) . "\n";
        }
    }
    fclose($file);
}



//mergeResult();

function formatResult()
{
    $file = fopen('/root/5sns_result.txt','r');
    if ($file)
    {
        $count = 0;
        while (!feof($file))
        {
            ++$count;
            $buffer = fgets($file,10240);
            $e = json_decode($buffer,true);

            echo $e['class'] ."\t". $e['name']."\t".$e['wxid'] ."\t". $e['qq'] ."\t". $e['weixin_url'] ."\t"
                . $e['weixin_short_url'] ."\t". $e['qrcode_url'] ."\t". $e['introduce'] . "\n";


            if($count == 5)
            {
                //break;
            }
        }
    }
    fclose($file);
}



formatResult();




