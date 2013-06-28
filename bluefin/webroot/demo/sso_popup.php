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

?><!DOCTYPE html>
<html lang="zh_CN">
<head>
    <meta charset="utf-8">
    <link type="text/css" href="/libs/jquery-boxer/jquery.fs.boxer.css" rel="stylesheet">
</head>
<body>
<?php
    //按照自己的方式检查是否登录
    if (!isset($_SESSION['user']))
    {//未登录
        //获取弹出登录的地址，第一个参数传false表示直接返回JSON结果而不是跳转，第二个参数可传状态会原样返回
        //第一个参数如果是callback://开头，登录成功后可以调用本页的脚本
        $authUrl = $client->getAuthorizeURL('callback://onLoginSuccess');
?>
        <div>未登录。</div>
        <div>
            请 <a href="<?php echo $authUrl; ?>" class="boxer" data-width="640" data-height="480">登录</a>
        </div>
<?php
    }
    else
    {
?>
        <div><?php echo $_SESSION['user']['profile_nick_name']; ?> 已登录</div>
        <div>
            <a href="logout.php">退出</a>
        </div>
<?php
    }
?>
    <script src="/libs/jquery/jquery.min.js"></script>
    <script src="/libs/jquery-boxer/jquery.fs.boxer.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $(".boxer").boxer();
        });

        function onLoginSuccess(data)
        {
            var thisPage = "<?php echo $_SERVER['REQUEST_URI']; ?>";

            if (thisPage.indexOf('?') != -1) {
                thisPage += '&';
            } else {
                thisPage += '?';
            }

            thisPage += 'ticket=' + data.ticket;

            if (data.state) {
                thisPage += '&state=' + data.state;
            }

            window.location.href = thisPage;
        }
    </script>
</body>
</html>