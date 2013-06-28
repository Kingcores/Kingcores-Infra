<?php

namespace WBT\Controller;

use Bluefin\App;
use Bluefin\Convention;
use Common\Data\Event;
use WBT\Controller\WBTControllerBase;
use WBT\Model\Weibotui\User;
use WBT\Model\Weibotui\Weibo;
use WBT\Model\Weibotui\PersonalProfile;
use WBT\Business\UserBusiness;
use WBT\Business\AuthBusiness;
use WBT\Business\SsoBusiness;
use WBT\Business\TuikeBusiness;

use Bluefin\HTML\Form;
use Bluefin\HTML\Button;
use Bluefin\HTML\Table;
use Bluefin\HTML\Link;
use Bluefin\HTML\CustomComponent;

class RegisterController extends WBTControllerBase
{
    public function indexAction()
    {
        //表单的字段
        $fields = [
           '_from' => [
                Form::FIELD_TAG => Form::COM_HIDDEN,
                Form::FIELD_VALUE => $this->_requestSource
           ],
           '_guide' => [
                Form::FIELD_TAG => Form::COM_CUSTOM,
                Form::FIELD_VALUE => <<<'HTML'
            <div class="progress">
                <div class="bar bar-info" style="width: 33%;">第1步：填写信息</div>
                <div class="bar" style="width: 33%;">第2步：激活帐号</div>
                <div class="bar" style="width: 34%;">第3步：注册成功</div>
            </div>
HTML
            ],
            'username' => [
                Form::FIELD_LABEL_ICON => 'icon-envelope',
                Form::FIELD_ALT_NAME => _DICT_('email'),
                Form::FIELD_CLASS => 'input-medium',
                'autocomplete' => 'off'
            ],
            'password' => [
                Form::FIELD_LABEL_ICON => 'icon-asterisk',
                Form::FIELD_CLASS => 'input-medium',
                Form::FIELD_CONFIRM => true,
                Form::FIELD_INLINE => true,
                'autocomplete' => 'off'
            ],
            '_eula' => [
                Form::FIELD_LABEL => _DICT_('eula'),
                Form::FIELD_LABEL_ICON => 'icon-file',
                Form::FIELD_TAG => Form::COM_TEXT_AREA,
                Form::FIELD_ID => 'textEula',
                Form::FIELD_EXCLUDED => true,
                Form::FIELD_MESSAGE => <<<'HTML'
&nbsp;&nbsp;<i class="icon-info-sign"></i><a href="javascript:bluefinBH.ajaxDialog('/register/eula', {closeButton: true});">点击全文阅读</a>
HTML
                ,
                'style' => "width: 97%;",
                'rows' => "5",
                'readonly'
            ],
            '_checkEula' => [
                Form::FIELD_TAG => Form::COM_CHECK_BOX,
                Form::FIELD_ID => 'checkboxEula',
                Form::FIELD_LABEL => '我同意以上协议',
            ]
        ];

        if ($this->_request->isPost())
        {
            if (!$this->_request->isSecure())
            {
                throw new \Bluefin\Exception\RequestException(null, \Bluefin\Common::HTTP_FORBIDDEN);
            }

            try
            {
                $inputs = Form::filterFormInputs(User::s_metadata(), $fields, $this->_request->getPostParams());

                $username = array_try_get($inputs, 'username');
                $password = array_try_get($inputs, 'password');
                $requestToken = $this->_request->getQueryParam('request');

                UserBusiness::registerWeibotui($username, $password, $requestToken);

                $this->_redirectWithSource('register/send_verify_email', ['email' => $username]);
            }
            catch (\Bluefin\Exception\InvalidRequestException $e)
            {
                $this->_view->set('_eventMessage', $e->getMessage());
                $this->_view->set('_eventAlertClass', ' alert-error');
            }
        }

        $form = Form::fromModelMetadata(
            User::s_metadata(),
            $fields,
            $this->_request->getPostParams(),
            ['action' => $this->_gateway->url(null, null, null, true)]
        );

        $form->legend = '<h4 class="status-title">注册微博推帐号</h4>';
        $form->bodyScript = <<<'JS'
            function checkEula() {
                if ($('#checkboxEula').attr('checked') == 'checked')
                {
                    $('#buttonSubmit').removeAttr('disabled');
                }
                else
                {
                    $('#buttonSubmit').attr('disabled', 'disabled');
                }
            }
JS;
        $form->initScript = <<<'JS'
            $.get('/register/eula', function(data) {
                $('#textEula').text($(data).text());
            });
            $('#checkboxEula').click(checkEula);
            checkEula();
JS;

        //设置表单按钮
        $form->addButtons([
            new Button('注册', null, ['id' => 'buttonSubmit', 'type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        $this->_view->set('form', (string)$form);
    }

    public function eulaAction()
    {
    }

    /**
     * 发送验证邮件。
     */
    public function sendVerifyEmailAction()
    {
        $email = $this->getRequest()->getQueryParam('email');
        _ARG_IS_SET('email', $email);

        $resend = $this->getRequest()->getQueryParam('resend');

        UserBusiness::sendVerificationEmail($email);

        $this->_redirectWithSource('register/verify_email', ['email' => $email, 'resend' => $resend]);
    }

    /**
     * 提示登录邮箱进行验证。
     */
    public function verifyEmailAction()
    {
        $email = $this->getRequest()->getQueryParam('email');
        _ARG_IS_SET('email', $email);

        $resend = $this->getRequest()->getQueryParam('resend');
        if ($resend)
        {
            $this->_view->set('_eventMessage', _APP_('The account verification email is resent successfully.'));
            $this->_view->set('_eventAlertClass', ' alert-success');
        }

        $activate = $this->getRequest()->getQueryParam('activate');
        if ($activate)
        {
            $this->_setEventMessage(Event::E_ACCOUNT_NONACTIVATED, Event::SRC_REG);
        }

        $this->_view->set('email', $email);
        $this->_view->set('resendUrl', _P('register/send_verify_email', ['email' => $email, 'resend' => 1, '_from' => b64_encode($this->_requestSource)]));
    }

    /**
     * 验证邮箱，激活帐号。
     */
    public function activateAction()
    {
        $token = $this->getRequest()->getQueryParam('token');
        _ARG_IS_SET('token', $token);

        $eventCode = UserBusiness::activateUser($token);
        if (Event::getEventLowerCode($eventCode) == Event::S_ACTIVATE_SUCCESS)
        {
            $this->_view->set('succeeded', true);
            return;
        }

        $this->_view->set('failureMessage', Event::getMessage($eventCode));
    }

    public function socialAction()
    {
        $auth = $this->_app->auth('weibotui');

        $type = $this->_request->getQueryParam('type');
        _ARG_IS_SET(_META_('weibotui.weibo.type'), $type);

        if (!(new \WBT\Model\Weibotui\WeiboType())->validate($type))
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $uid = $this->_request->getQueryParam('id');
        _ARG_IS_SET(_META_('weibotui.weibo.uid'), $uid);

        if ($auth->isAuthenticated())
        {
            UserBusiness::bindWeiboToWeibotui($auth->getUniqueID(), $type, $uid);
            $this->_backToRequestSource();
        }

        $skip = $this->_request->getQueryParam('skip', 1);
        \Bluefin\Data\Type::convertBool(null, $skip);

        $this->_view->set('bindUrl', _U('auth/index', [Convention::KEYWORD_REQUEST_FROM => $this->_gateway->path('user/account/bind', ['type' => $type, 'id' => $uid], true)]));

        if ($this->_request->isPost())
        {
            $username = trim($this->_request->getPostParam('username'));
            $password = $this->_request->getPostParam('password');
            $passwordConfirm = $this->_request->getPostParam('password_confirm');

            if ($password != $passwordConfirm)
            {
                $this->_view->set('_eventMessage', _APP_('Two passwords are not the same.'));
                $this->_transferPostStates();
                return;
            }

            try
            {
                $weibo = UserBusiness::registerWeibotuiFromSocial($username, $password, $type, $uid);
            }
            catch (\Bluefin\Exception\RequestException $re)
            {
                $this->_view->set('_eventMessage', $re->getMessage());
                $this->_transferPostStates();
                return;
            }

            $this->_redirectWithEvent(Event::success(Event::SRC_SNA, Event::S_REGISTER_BY_SNA), ['%type%' => _META_('weibotui.weibo_type.' . $type), '%sna_name%' => $weibo->getDisplayName(), '%username%' => $username], _C('config.custom.url.social_entry'), ['_ticket' => SsoBusiness::issueUserTicket($weibo->getUser())]);
        }
        else
        {
            $this->_view->set('type', $type);
            $this->_view->set('id', $uid);
            $this->_view->set('skip', $skip);
        }
    }

    public function tuikeAction()
    {
        $auth = $this->_requireAuth('weibotui');
        $this->_validateAccountStatus($auth);

        //表单的字段
        $fields = [
           '_from' => [
                Form::FIELD_TAG => Form::COM_HIDDEN,
                Form::FIELD_VALUE => $this->_requestSource
           ],
           '_guide' => [
                Form::FIELD_TAG => Form::COM_CUSTOM,
                Form::FIELD_VALUE => <<<'HTML'
            <div class="progress">
                <div class="bar bar-info" style="width: 33%;">第1步：完善个人信息</div>
                <div class="bar" style="width: 33%;">第2步：添加社交媒体渠道</div>
                <div class="bar" style="width: 34%;">第3步：推客注册成功</div>
            </div>
HTML
            ],
            'mobile' => [
                Form::FIELD_REQUIRED => true
            ],
            'mobile_auth_code' => [
                Form::FIELD_TAG => Form::COM_MOBILE_AUTH,
                Form::FIELD_LABEL => _APP_('Mobile Authentication Code'),
                Form::FIELD_REQUIRED => true,
                'send' => 'javascript:sendMobileAuthCode();'
            ],
            'qq'
        ];

        if ($this->_request->isPost())
        {
            try
            {
                $inputs = Form::filterFormInputs(PersonalProfile::s_metadata(), $fields, $this->_request->getPostParams());

                $realSmsAuthCode = $this->_app->session()->get('sms_auth_code');

                // 清空验证码，防止用同一个验证码注册多次
                $this->_app->session()->remove('sms_auth_code');

                $mobile = array_try_get($inputs, 'mobile');
                $mobileAuthCode = array_try_get($inputs, 'mobile_auth_code');

                // 09321012101 内部测试用
                if ($mobile != '09321012101')
                {
                    if($realSmsAuthCode != $mobileAuthCode )
                    {
                        throw new \Bluefin\Exception\InvalidRequestException(_APP_('Wrong mobile authentication code.'));
                    }
                }

                $qq = array_try_get($inputs, 'qq');

                UserBusiness::registerTuike($auth->getUniqueID(), [
                    PersonalProfile::MOBILE => $mobile,
                    PersonalProfile::QQ => $qq,
                ]);

                $this->_gateway->redirect($this->_gateway->path('register/inventory', null, true));
            }
            catch (\Bluefin\Exception\InvalidRequestException $e)
            {
                $this->_view->set('_eventMessage', $e->getMessage());
                $this->_view->set('_eventAlertClass', 'alert-error');
            }

            $data = $this->_request->getPostParams();
        }
        else
        {
            $data = $auth->getData('profile');
        }

        $form = Form::fromModelMetadata(
            PersonalProfile::s_metadata(),
            $fields,
            $data
        );

        $form->legend = '<h4 class="status-title">注册为推客</h4>';

        //设置表单按钮
        $form->addButtons([
            new Button('下一步', null, ['type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('返回', $this->_requestSource, ['class' => 'btn-cancel']),
        ]);

        $this->_view->set('form', (string)$form);
    }

    public function inventoryAction()
    {
        $auth = $this->_requireAuth('weibotui');
        $this->_validateAccountStatus($auth);

        if (!UserBusiness::isTuike())
        {
            throw new \Bluefin\Exception\InvalidRequestException(_APP_('You are not a tweeter.'));
        }

        $this->_setUserProfileAndRolesInView();

        //广告活动数据
        $data = TuikeBusiness::getWeiboList($auth->getUniqueID(), $paging, $outputColumns);

        $excludedList = TuikeBusiness::getInventoryWeiboIDList($auth->getUniqueID());

        //表单Header
        $shownOptions = [
            'type' => [
                Table::COLUMN_HEADER_STYLE => 'width:60px;',
                Table::COLUMN_CELL_STYLE => 'vertical-align:middle;'
            ],
            'display_name' => [
                Table::COLUMN_HEADER_STYLE => 'width:160px;',
                Table::COLUMN_CELL_STYLE => 'vertical-align:middle;',
                Table::COLUMN_VAR_TEXT => <<<'HTML'
                <div class="pull-left" style="padding-top: 5px;"><img src="{{this.avatar_s}}">&nbsp;&nbsp;</div><div class="pull-left"><a href="{{this.url}}" target="_blank">{{this.display_name}}</a><br>{{this.gender|meta='weibotui.gender'}}<br>{{ this.location }}</div>
HTML
            ],
            'description' => [
                Table::COLUMN_CELL_STYLE => 'vertical-align:middle;'
            ],
            'num_follower' => [
                Table::COLUMN_HEADER_STYLE => 'width:60px;',
                Table::COLUMN_CELL_STYLE => 'vertical-align:middle;text-align:right;',
            ],
            '_operations' => [
                Table::COLUMN_HEADER_STYLE => 'width:60px;',
                Table::COLUMN_CELL_STYLE => 'vertical-align:middle;',
                Table::COLUMN_OPERATIONS => [
                    new Link(
                        '注册渠道',
                        "#",
                        [
                            'class' => 'btn btn-success btn-mini',
                            'visible' => function($row) use ($excludedList) { return !in_array($row[Weibo::WEIBO_ID], $excludedList); }
                        ]
                    ),
                    new CustomComponent(
                        '已注册',
                        [
                            'visible' => function($row) use ($excludedList) { return in_array($row[Weibo::WEIBO_ID], $excludedList); }
                        ]
                    )
                ]
            ]
        ];

        //构造表单
        $table = Table::fromDbData($data, $outputColumns, Weibo::WEIBO_ID, $paging, $shownOptions,
            ['class' => 'table-bordered table-striped table-hover']
        );

        $callback = b64_encode($this->_request->getFullRequestUri(), null, true);

        $this->_view->set('table', $table);
        $this->_view->set('snAuth', AuthBusiness::getAllSupportedAuthTypes($callback, true));
    }
}