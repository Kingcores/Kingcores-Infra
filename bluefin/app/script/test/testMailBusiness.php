<?php
require_once '../../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use WBT\Business\MailBusiness;



testSendMail();

function testSendMail()
{
    $app = \Bluefin\App::getInstance();

    //$to = 'cuiguangbin@kingcores.com';
    $to = 'lvliangbo@kingcores.com';
    $subject = '测试发送邮件';
    $htmlContent = '<p>测试<span style="color:#548dd4;">发送邮件</span> 大<span style="color:#ff0000;">了就发啦
</span>大连市金法拉是  <a href="http://www.weibotui.com/">http://www.weibotui.com</a> dsalfjal dfjla
</p>
<p>
    asdfkjasdflsadalf 肯<strong>定是辣椒粉 </strong>adsl飞机啊了
</p>
<p>
    <br />
</p>';

    MailBusiness::sendMail($to,$subject,$htmlContent);

    $to = 'wanshanju@gmail.com';
    MailBusiness::sendMail($to,$subject,$htmlContent);


}