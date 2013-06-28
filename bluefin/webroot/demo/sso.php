<?php

require_once '../../lib/WBTSdk/WBTPrivateClient.php';

session_start();

//创建客户端，如果是线上环境最后一个参数改为false
$client = new WBTPrivateClient('10001', 'b084ff59d3588b62e40e81c06186a709', true);

//检查是否有新登录凭据
$userProfile = $client->verifyTicket();

if (!empty($userProfile))
{//有新凭据，且成功获取用户信息

    //按照自己的方式设置登录验证
    $_SESSION['user'] = $userProfile;

    //重载页面
    $client->reloadWithoutTicket();
}

//按照自己的方式检查是否登录
if (!isset($_SESSION['user']))
{//未登录
    //直接跳转到登录
    $client->singleSignOn($_SERVER['REQUEST_URI']);

    //或者提供一个登录链接
    //$authUrl = $client->getAuthorizeURL($_SERVER['REQUEST_URI']);
}

//成功登陆啦，打印用户信息
echo $_SESSION['user']['profile_nick_name'] . ' ' . '已登录，<a href="logout.php">退出</a>';
