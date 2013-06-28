<?php

namespace WBT\Controller;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Auth\AuthHelper;
use WBT\Business\UserBusiness;
use WBT\Business\AuthBusiness;
use WBT\Business\SsoBusiness;
use WBT\Model\Weibotui\UserRole;
use WBT\Model\Weibotui\UserStatus;
use Common\Data\Event;
use Bluefin\HTML\Form;
use Bluefin\HTML\Button;
use Common\Helper\WBTOAuth2;
use WBT\Model\Weibotui\User;

class AuthController extends WBTControllerBase
{
    /**
     * 登录界面入口。
     */
    public function indexAction()
    {
        if ($this->_request->isPost())
        {
            if (!$this->_request->isSecure())
            {
                throw new \Bluefin\Exception\RequestException(null, \Bluefin\Common::HTTP_FORBIDDEN);
            }

            if (isset($this->_requestSource) && is_abs_url($this->_requestSource))
            {
                throw new \Bluefin\Exception\InvalidRequestException();
            }

            $flag = AuthBusiness::login($this->_request->getPostParams());

            if (AuthHelper::SUCCESS === $flag)
            {
                $auth = $this->_app->auth('weibotui');

                $this->_validateAccountStatus($auth->getIdentity(), $auth->getData('status'));

                if (isset($this->_requestSource))
                {
                    $this->_backToRequestSource();
                }

                $preferences = json_decode($auth->getData('preferences'), true);

                $defaultRole = array_try_get($preferences, 'home_role');
                if (!AuthBusiness::isUserHasRole($defaultRole))
                {
                    $defaultRole = '';
                }

                $this->_gateway->redirect(UserBusiness::getHomePageByRole($auth->getUniqueID() ,$defaultRole));
            }
            else
            {
                $this->_setEventMessage($flag, Event::SRC_AUTH, Event::LEVEL_ERROR);
                $this->_transferPostStates();
            }
        }

        $this->_view->set('auth', AuthBusiness::getAllSupportedAuthTypes($this->_requestSource));
    }

    public function authorizeAction()
    {
        //验证必要的参数
        $params = $this->_app->request()->getQueryParams();

        $redirectUrl = array_try_get($params, 'redirect_uri');
        _ARG_IS_SET('redirect_uri', $redirectUrl);

        $clientId = array_try_get($params, 'client_id');
        _ARG_IS_SET('client_id', $clientId);

        $signature = array_try_get($params, 'signature');
        _ARG_IS_SET('signature', $signature);

        $state = array_try_get($params, 'state');

        //验证请求的来源和合法性
        $client = WBTOAuth2::verify($this->_app->request()->getFullRequestUri(), $clientId, $signature);

        if ($this->_request->isPost())
        {//提交登录
            if (!$this->_request->isSecure())
            {
                throw new \Bluefin\Exception\RequestException(null, \Bluefin\Common::HTTP_FORBIDDEN);
            }

            $authRequest = $this->_request->getPostParams();
            $authRequest = array_get_all($authRequest, ['username', 'password']);

            $flag = AuthBusiness::ssoLogin($authRequest);

            if (AuthHelper::SUCCESS === $flag)
            {
                $userProfile = UserBusiness::getUserProfile([User::USERNAME => $authRequest['username']]);

                $this->_validateAccountStatus($authRequest['username'], $userProfile['status']);

                $this->_authorizeResponse($redirectUrl, $userProfile['user_id'], $state);
            }
            else
            {
                $this->_setEventMessage($flag, Event::SRC_AUTH, Event::LEVEL_ERROR);
            }
        }
        else
        {
            $confirm = array_try_get($params, 'confirm');

            $auth = $this->_app->auth('weibotui');

            if (isset($confirm))
            {//是确认链接

                if ($confirm == 1)
                {//已点击确认
                    if ($auth->isAuthenticated())
                    {
                        $this->_authorizeResponse($redirectUrl, $auth->getUniqueID(), $state);
                    }

                    throw new \Bluefin\Exception\UnauthorizedException();
                }
                else if ($confirm == 2)
                {
                    $requestToken = $this->_request->getQueryParam('request');
                    if (isset($requestToken))
                    {
                        $userId = UserBusiness::getRegisteredUserByRequestToken($requestToken);
                        if (isset($userId))
                        {
                            $this->_authorizeResponse($redirectUrl, $userId, $state);
                        }
                    }

                    throw new \Bluefin\Exception\UnauthorizedException();
                }

                throw new \Bluefin\Exception\InvalidRequestException();
            }
            else
            {
                if ($auth->isAuthenticated())
                {
                    //转到确认页
                    $confirmUrl = $this->_gateway->url(null, ['redirect_uri' => $redirectUrl, 'state' => $state, 'confirm' => 1]);
                    $confirmUrl = WBTOAuth2::sign($confirmUrl, $clientId, $client->getSecret());

                    $this->changeView('confirm');
                    $this->_view->set('confirmUrl', $confirmUrl);
                    $this->_view->set('avatarUrl', $auth->getData('profile_avatar'));
                    $this->_view->set('nickName', $auth->getData('profile_nick_name'));
                }

                $requestToken = $this->_request->getQueryParam('request');
                if (isset($requestToken))
                {
                    $userId = UserBusiness::getRegisteredUserByRequestToken($requestToken);
                    if (isset($userId))
                    {
                        $userProfile2 = UserBusiness::getUserProfile([User::USER_ID => $userId]);

                        $this->_validateAccountStatus($userProfile2['username'], $userProfile2['status']);

                        $confirmUrl2 = $this->_gateway->url(null, ['redirect_uri' => $redirectUrl, 'state' => $state, 'confirm' => 2]);
                        $confirmUrl2 = WBTOAuth2::sign($confirmUrl2, $clientId, $client->getSecret());

                        $this->changeView('confirm');
                        $this->_view->set('confirmUrl2', $confirmUrl2);
                        $this->_view->set('avatarUrl2', $userProfile2['profile_avatar']);
                        $this->_view->set('nickName2', $userProfile2['profile_nick_name']);
                    }
                }
            }
        }

        //当前URL
        $reqToken = \Common\Helper\UniqueIdentity::generate(32);
        $backUrl = WBTOAuth2::sign($this->_gateway->url(null, ['request' => $reqToken]), $clientId, $client->getSecret());

        //签名后的注册地址
        $registerUrl = $this->_gateway->path('register', ['_from' => b64_encode($backUrl), 'request' => $reqToken]);

        //表单的字段
        $fields = [
            'username' => [
                Form::FIELD_ALT_NAME => _DICT_('email'),
                'autocomplete' => 'off'
            ],
            'password' => [
                'autocomplete' => 'off'
            ]
        ];

        $form = Form::fromModelMetadata(
            User::s_metadata(),
            $fields,
            $this->_request->getPostParams(),
            ['class' => 'form-horizontal', 'showRequired' => false, 'action' => $this->_gateway->url(null, null, null, true)]
        );

        //设置表单按钮
        $form->addButtons([
            new Button('登录', null, ['id' => 'buttonSubmit', 'type' => Button::TYPE_SUBMIT, 'class' => 'btn-success']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        $this->_view->set('clientName', $client->getName());
        $this->_view->set('registerUrl', $registerUrl);
        $this->_view->set('form', (string)$form);
    }

    public function jsCallbackAction()
    {
        $params = $this->_app->request()->getQueryParams();

        $callback = array_try_get($params, 'callback');
        _ARG_IS_SET('callback', $callback);

        $data = array_try_get($params, 'data');
        _ARG_IS_SET('data', $data);

        $data = base64_decode($data);

        echo <<<HTML
<!DOCTYPE html>
<html lang="zh_CN"xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
</head>
<body>
    <script type="text/javascript">
        var data = {$data};
        window.parent.{$callback}(data);
    </script>
</body>
</html>
HTML;
    }

    /**
     * 注销登录。
     */
    public function logoutAction()
    {
        AuthBusiness::logout();
        $this->_gateway->redirect(App::getInstance()->rootUrl());
    }

    protected function _authorizeResponse($redirectUri, $userId, $state)
    {
        $result = ['ticket' => SsoBusiness::issueUserTicket($userId), 'state' => $state];

        if ('0' === $redirectUri)
        {
            $this->_sendJsonAndExit($result);
        }
        else if (substr($redirectUri, 0, 11) == 'callback://')
        {
            $result = json_encode($result);
            $script = substr($redirectUri, 11);

            if ($this->_request->isSecure())
            {
                $this->_gateway->redirect(
                    $this->_gateway->url('auth/js_callback', ['callback' => $script, 'data' => base64_encode($result)], null, false)
                );
            }

            echo <<<HTML
<!DOCTYPE html>
<html lang="zh_CN"xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
</head>
<body>
    <script type="text/javascript">
        var data = {$result};
        window.parent.{$script}(data);
    </script>
</body>
</html>
HTML;
            throw new \Bluefin\Exception\SkipException();
        }

        $this->_gateway->redirect(build_uri($redirectUri, $result));
    }
}