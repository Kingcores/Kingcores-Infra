<?php

namespace WBT\Controller\Form;

use Bluefin\Controller;
use Bluefin\Data\Model;
use WBT\Model\Weibotui\UserDepositRecord;
use WBT\Model\Weibotui\PaymentMethod;
use WBT\Model\Weibotui\IncomeType;
use Bluefin\HTML\Form;
use Bluefin\HTML\Button;

class UserDepositController extends Controller
{
    public function showAction()
    {
        $this->_requireAuth('weibotui');

        //表单的字段
        $fields = [
            'amount' => [
                Form::FIELD_VALUE => '5000.00',
                Form::FIELD_MESSAGE => '最小充值金额为￥100.00',
            ],
            'income_type' => [
                Form::FIELD_TAG => Form::COM_HIDDEN,
                Form::FIELD_VALUE => IncomeType::ADVERTISER_DEPOSIT
            ],
            'payment_method' => [
                Form::FIELD_TAG => Form::COM_RADIO_GROUP,
                Form::FIELD_LABEL => _META_('weibotui.payment_method'),
                Form::FIELD_DATA => PaymentMethod::getDictionary(),
                Form::FIELD_CLASS => 'inline',
                Form::FIELD_REQUIRED => true
            ]
        ];

        $mockPay = ENV == 'dev';
        if ($mockPay)
        {
            $fields['mock_pay'] = [
                Form::FIELD_TAG => Form::COM_RADIO_GROUP,
                Form::FIELD_LABEL => '模拟支付',
                Form::FIELD_DATA => [ '1' => '成功', '0' => '失败' ],
                Form::FIELD_VALUE => '1',
                Form::FIELD_CLASS => 'inline'
            ];
        }

        $form = Form::fromModelMetadata(
            UserDepositRecord::s_metadata(),
            $fields,
            null,
            ['class' => 'form-horizontal', 'action' => $this->_app->basePath() . 'user/asset/deposit']
        );

        $form->legend = _APP_('Deposit');

        if ($mockPay)
        {
            $form->legend .= '模拟';
        }

        //设置表单按钮
        $form->addButtons([
            new Button('付款', null, ['type' => Button::TYPE_SUBMIT, 'class' => 'btn-primary']),
            new Button('取消', null, ['class' => 'btn-cancel']),
        ]);

        echo $form;
        echo \Bluefin\HTML\SimpleComponent::$scripts;
    }
}
