<?php
require_once '../../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use Upyun\WeibotuiUpyun;


testUpyun();

testUploadRemoteImage();
function testUpyun()
{
    $app = \Bluefin\App::getInstance();

    $fileName = '/kingcores/www/weibotui.com/src/webroot/images/logo.png';
    $url = WeibotuiUpyun::uploadImage($fileName);
    echo "url : $url\n";

    $fileName = '/tmp/noting.png';
    $url = WeibotuiUpyun::uploadImage($fileName);
    echo "url : $url\n";

}

function testUploadRemoteImage()
{
    $app = \Bluefin\App::getInstance();

    $fileName = 'http://mmsns.qpic.cn/mmsns/AbruuZ3ILClYlNzraaXpf4sZYs0Quv75sOubBtpak24ukw4fkUwLxg/0';
    //$fileName = 'http://weibotui.com/images/logo.png';

    $url = WeibotuiUpyun::uploadImage($fileName);
    echo "url : $url\n";
}
